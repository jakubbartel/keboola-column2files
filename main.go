package main

import (
	"fmt"
	"io"
	"os"
	"time"
)

func main() {
	var t time.Time

	dataPath, ok := os.LookupEnv("KBC_DATADIR")
	if !ok {
		dataPath = "/data/"
	}

	filePath := dataPath + "in/tables/NaklStatistikyAllShops.csv"
	outputDir := dataPath + "out/tables/NaklStatistikyAllShops.csv"

	Copy(
		dataPath+"in/tables/NaklStatistikyAllShops.csv.manifest",
		dataPath+"out/tables/NaklStatistikyAllShops.csv.manifest",
	)

	os.Mkdir(outputDir, 0755)

	t = time.Now()
	split(filePath, outputDir, true)
	fmt.Println("split took", time.Since(t))
}

// Copies a file.
func Copy(src string, dst string) error {
	// Open the source file for reading
	s, err := os.Open(src)
	if err != nil {
		return err
	}
	defer s.Close()

	// Open the destination file for writing
	d, err := os.Create(dst)
	if err != nil {
		return err
	}

	// Copy the contents of the source file into the destination file
	if _, err := io.Copy(d, s); err != nil {
		d.Close()
		return err
	}

	// Return any errors that result from closing the destination file
	// Will return nil if no errors occurred
	return d.Close()
}
