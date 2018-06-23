package main

import (
	"bufio"
	"github.com/jakubbartel/keboola-split-by-value-processor/fastcsv"
	"log"
	"os"
	"time"
)

const MaxWriters = 2000

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
		log.Fatalf("cannot create file \"%s\", err: %s", outputPath, err)
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
		log.Fatalf("cannot open file \"%s\", err: %s", filePath, err)
	}

	b := buffer{
		values: make(map[string]*bufferValue, 100000),
	}

	for {
		t := time.Now()

		ret, err := f.Seek(0, 0)
		if err != nil {
			log.Fatalf("cannot on input file, err: %s", err)
		} else if ret != 0 {
			log.Fatalf("cannot seek to 0 on input file")
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
			log.Fatalf("error while reading csv, err: %s", err)
		}

		toBreak := b.rejected == 0

		b.close()

		log.Printf("one pass took %s", time.Since(t))

		if toBreak {
			break
		}
	}

	f.Close()
}
