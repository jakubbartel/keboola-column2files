package fastcsv

import (
	"testing"
	"reflect"
	"bytes"
)

func TestSimpleRows(t *testing.T) {
	csvData := []byte("\"col1\",\"col2\"\n" +
		"\"simple\",\"simple1\"\n" +
		"\"simple\",\"simple2\"\n" +
		"\"simple\",\"simple3\"")

	r := bytes.NewReader(csvData)

	cr := NewReader(r)

	assertFields := [][][]byte{
		{[]byte("col1"), []byte("col2")},
		{[]byte("simple"), []byte("simple1")},
		{[]byte("simple"), []byte("simple2")},
		{[]byte("simple"), []byte("simple3")},
	}

	assertReader(t, csvData, &cr, assertFields)
}

func TestReadEscapedEnclosures(t *testing.T) {
	csvData := []byte("\"col1\",\"col2\"\n" +
		"\"\"\"escaped\"\"\",\"\"\"escaped1\"\"\"\n" +
		"\"escaped\"\"\",\"\"\"escaped2\"")

	r := bytes.NewReader(csvData)

	cr := NewReader(r)

	assertFields := [][][]byte{
		{[]byte("col1"), []byte("col2")},
		{[]byte("\"escaped\""), []byte("\"escaped1\"")},
		{[]byte("escaped\""), []byte("\"escaped2")},
	}

	assertReader(t, csvData, &cr, assertFields)
}

func TestReadMultiLineCsv(t *testing.T) {
	csvData := []byte("\"col1\",\"col2\"\n" +
		"\"multi\nline row\",\"multiline2\"")

	r := bytes.NewReader(csvData)

	cr := NewReader(r)

	assertFields := [][][]byte{
		{[]byte("col1"), []byte("col2")},
		{[]byte("multi\nline row"), []byte("multiline2")},
	}

	assertReader(t, csvData, &cr, assertFields)
}

func TestLargeCsv(t *testing.T) {
	bb := bytes.NewBuffer([]byte{})

	assertCsv := [][][]byte{}

	for i := 0; i < 10000; i++ {
		row := [][]byte{}
		for j := 0; j < 8; j++ {
			if j != 0 {
				bb.WriteRune(',')
			}
			bb.Write([]byte("\"column\""))
			row = append(row, []byte("column"))
		}
		bb.WriteRune('\n')
		assertCsv = append(assertCsv, row)
	}

	cr := NewReader(bb)

	assertReader(t, bb.Bytes(), &cr, assertCsv)
}

func assertReader(t *testing.T, data []byte, cr *Reader, assertFields [][][]byte) {
	ri := 0

	rowsData := bytes.NewBuffer([]byte{})

	for cr.Next() {
		row := cr.Fields()

		rowsData.Write(cr.Row())

		if ri < len(assertFields) {
			if !reflect.DeepEqual(row, assertFields[ri]) {
				t.Errorf("not equal on the row %d actual:%s expected:%s", ri, row, assertFields[ri])
			}
		}

		ri++
	}
	if err := cr.Err(); err != nil {
		t.Errorf("error at the end of reading %s", err)
	}

	if ri < len(assertFields) {
		t.Errorf("did not read all the rows, actual:%d expected:%d", ri, len(assertFields))
	} else if ri > len(assertFields) {
		t.Errorf("read more rows than expected, actual:%d expected:%d", ri, len(assertFields))
	}

	if !bytes.Equal(rowsData.Bytes(), data) {
		t.Errorf("bytes of the data differes from read rows\nactual:\n%s\nexpected:\n%s", rowsData.Bytes(), data)
	}
}
