package main

import (
	"bufio"
	"fmt"
	"github.com/jakubbartel/keboola-split-by-value-processor/fastcsv"
	"os"
	"time"
)

const MaxWriters = 5000

type buffer struct {
	values   map[string]*bufferValue
	opened   int
	rejected int
}

type bufferValue struct {
	file   *os.File
	writer *bufio.Writer
	done   bool
}

func (b *buffer) getWriter(field []byte, outputPath string) (w *bufio.Writer) {
	if bval, ok := b.values[string(field)]; ok {
		if bval.done {
			return nil
		}

		return bval.writer
	}

	if b.opened >= MaxWriters {
		b.rejected += 1
		return nil
	}

	f, err := os.OpenFile(outputPath, os.O_CREATE|os.O_WRONLY, 0600)
	if err != nil {
		panic("cannot create file")
	}

	w = bufio.NewWriterSize(f, 16384)

	b.values[string(field)] = &bufferValue{
		file:   f,
		writer: w,
		done:   false,
	}
	b.opened += 1

	return w
}

func (b *buffer) close() {
	for _, bval := range b.values {
		if bval.done || bval.file == nil {
			continue
		}

		bval.writer.Flush()
		bval.file.Close()

		bval.writer = nil
		bval.file = nil

		bval.done = true

		b.opened -= 1
	}

	b.rejected = 0
}

func split(filePath string, outputDir string, skipHeader bool) {
	f, err := os.Open(filePath)
	if err != nil {
		panic("cannot open file")
	}

	b := buffer{
		values: make(map[string]*bufferValue, 100000),
	}

	for {
		t := time.Now()

		ret, err := f.Seek(0, 0)
		if err != nil {
			panic("cannot seek")
		} else if ret != 0 {
			panic("not seek to 0")
		}

		r := fastcsv.NewReader(f)

		if skipHeader {
			r.Next()
		}

		for r.Next() {
			fields := r.Fields()

			outputPath := outputDir + "/" + string(fields[1])

			w := b.getWriter(fields[1], outputPath)
			if w == nil {
				continue
			}

			w.Write(r.Row())
		}
		if err := r.Err(); err != nil {
			panic(err)
		}

		toBreak := b.rejected == 0

		b.close()

		fmt.Println("one pass took", time.Since(t))

		if toBreak {
			break
		}
	}

	f.Close()
}
