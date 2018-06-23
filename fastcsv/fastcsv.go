package fastcsv

import (
	"io"
)

type bufferedReader struct {
	r       io.Reader
	rawData []byte
	data    []byte
	cursor  int
}

func (b *bufferedReader) more() error {
	if len(b.data) == cap(b.data) {
		temp := make([]byte, len(b.data), 2*len(b.data)+1)
		tempRaw := make([]byte, len(b.data), 2*len(b.data)+1)

		copy(temp, b.data)
		copy(tempRaw, b.rawData)

		b.data = temp
		b.rawData = tempRaw
	}

	// read the new bytes onto the end of the buffer
	n, err := b.r.Read(b.data[len(b.data):cap(b.data)])
	b.data = b.data[:len(b.data)+n]

	b.rawData = b.rawData[:len(b.data)]
	copy(b.rawData[len(b.data)-n:len(b.data)], b.data[len(b.data)-n:len(b.data)])

	return err
}

func (b *bufferedReader) reset() {
	copy(b.data, b.data[b.cursor:])
	copy(b.rawData, b.rawData[b.cursor:])

	b.data = b.data[:len(b.data)-b.cursor]
	b.rawData = b.rawData[:len(b.rawData)-b.cursor]

	b.cursor = 0
}

type fields struct {
	fieldStart int
	buffer     bufferedReader
	hitEOL     bool
	row        []byte
	field      []byte
	err        error
}

func (fs *fields) reset() {
	fs.buffer.reset()
	fs.row = nil
	fs.field = nil
	fs.fieldStart = 0
	fs.hitEOL = false
}

func (fs *fields) nextUnquotedField() bool {
	const sizeEOL = 1
	const sizeDelim = 1

	for {
		// next byte
		if fs.buffer.cursor >= len(fs.buffer.data) {
			err := fs.buffer.more()
			if err != nil {
				if err == io.EOF {
					end := fs.buffer.cursor
					// fs.row = fs.buffer.data[fs.rowStart:end]
					fs.field = fs.buffer.data[fs.fieldStart:end]
					fs.hitEOL = true
					fs.err = err
					return true
				}
				fs.err = err
				return false
			}
		}

		ch := fs.buffer.data[fs.buffer.cursor]
		fs.buffer.cursor++

		// handle byte
		switch ch {
		case ',':
			fs.field = fs.buffer.data[fs.fieldStart : fs.buffer.cursor-sizeDelim]
			// fs.row = fs.buffer.data[fs.rowStart : fs.buffer.cursor-sizeEOL]
			fs.fieldStart = fs.buffer.cursor
			return true
		case '\n':
			fs.field = fs.buffer.data[fs.fieldStart : fs.buffer.cursor-sizeEOL]
			// fs.row = fs.buffer.data[fs.rowStart : fs.buffer.cursor-sizeEOL]
			fs.hitEOL = true
			return true
		default:
			continue
		}
	}
}

func (fs *fields) nextQuotedField() bool {
	// skip past the initial quote rune
	fs.buffer.cursor++

	start := fs.buffer.cursor

	writeCursor := fs.buffer.cursor
	quoteCount := 0 // count consecutive quotes

	for {
		// next byte
		// if fs.buffer.cursor+1 >= len(fs.buffer.data) {
		if fs.buffer.cursor >= len(fs.buffer.data) {
			err := fs.buffer.more()
			if err != nil {
				fs.field = fs.buffer.data[start:writeCursor]
				fs.hitEOL = true
				fs.err = err

				return fs.err == io.EOF
			}
		}

		ch := fs.buffer.data[fs.buffer.cursor]
		fs.buffer.cursor++

		//if ch == 'i' {
		//	s := string(fs.buffer.rawData)
		//	fmt.Println(s)
		//}

		// handle byte
		switch ch {
		case ',':
			if quoteCount%2 != 0 {
				fs.field = fs.buffer.data[start:writeCursor]
				fs.hitEOL = false

				return true
			}
		case '\n':
			if quoteCount%2 != 0 {
				fs.field = fs.buffer.data[start:writeCursor]
				fs.hitEOL = true

				return true
			}
		case '"':
			quoteCount++

			// only write odd-numbered quotation marks
			if quoteCount%2 == 1 {
				continue
			}
		}

		quoteCount = 0
		writeCursor++

		// copy the current rune onto writeCursor if writeCursor !=
		// buffer.cursor
		if writeCursor != fs.buffer.cursor {
			copy(
				fs.buffer.data[writeCursor:writeCursor+1],
				fs.buffer.data[fs.buffer.cursor:fs.buffer.cursor+1],
			)
		}
	}
}

func (fs *fields) nextField() bool {
	if first := fs.buffer.data[fs.buffer.cursor]; first == '"' {
		return fs.nextQuotedField()
	}

	return fs.nextUnquotedField()
}

func (fs *fields) next() bool {
	if fs.hitEOL {
		return false
	}

	if fs.buffer.cursor >= len(fs.buffer.data) {
		err := fs.buffer.more()
		if err != nil {
			fs.err = err
			return false
		}
	}

	hasNext := fs.nextField()

	fs.row = fs.buffer.rawData[0:fs.buffer.cursor]
	// s := string(fs.row)
	// fmt.Println(s)

	return hasNext
}

type Reader struct {
	fields       fields
	fieldsBuffer [][]byte
	rawRow       []byte
}

// Scans in the next row
func (r *Reader) Next() bool {
	if r.fields.err != nil {
		return false
	}

	r.fields.reset()
	r.fieldsBuffer = r.fieldsBuffer[:0]

	for r.fields.next() {
		r.fieldsBuffer = append(r.fieldsBuffer, r.fields.field)
	}

	r.rawRow = r.fields.row

	// CRLF support: if there are fields in this row, and the last field ends
	// with `\r`, then it must have been part of a CRLF line ending, so drop
	// the `\r`.
	if len(r.fieldsBuffer) > 0 {
		lastField := r.fieldsBuffer[len(r.fieldsBuffer)-1]

		if len(lastField) > 0 && lastField[len(lastField)-1] == '\r' {
			lastField = lastField[:len(lastField)-1]
			r.fieldsBuffer[len(r.fieldsBuffer)-1] = lastField
		}
	}

	// Handle CSVs that end with a blank last line
	if len(r.fieldsBuffer) == 0 {
		r.fields.err = io.EOF
		return false
	}

	return true
}

// Returns the last row of fields encountered. These fields are only valid
// until the next call to Next() or Read().
func (r *Reader) Fields() [][]byte {
	return r.fieldsBuffer
}

func (r *Reader) Row() []byte {
	return r.rawRow
}

// Return the last error encountered; returns nil if no error was encountered
// or if the last error was io.EOF.
func (r *Reader) Err() error {
	if r.fields.err != io.EOF {
		return r.fields.err
	}
	return nil
}

// Read and return the next row and/or any errors encountered. The byte slices
// are only valid until the next call to Next() or Read(). Returns nil, io.EOF
// when the file is consumed.
func (r *Reader) Read() ([][]byte, error) {
	if r.Next() {
		return r.fieldsBuffer, nil
	}
	return nil, r.fields.err
}

// Constructs a new Reader from a source CSV io.Reader
func NewReader(r io.Reader) Reader {
	return Reader{
		fields: fields{
			buffer: bufferedReader{
				r:       r,
				data:    make([]byte, 0, 1024),
				rawData: make([]byte, 0, 1024),
			},
		},
		fieldsBuffer: make([][]byte, 0, 64),
	}
}
