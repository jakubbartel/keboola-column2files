package main

import (
	"io"
	"io/ioutil"
	"log"
	"os"
	"regexp"
	"time"
)

func main() {
	var t time.Time

	dataPath, ok := os.LookupEnv("KBC_DATADIR")
	if !ok {
		dataPath = "/data/"
	}

	inDir := dataPath + "in/tables/"
	outDir := dataPath + "out/tables/"

	files, err := ioutil.ReadDir(inDir)
	if err != nil {
		log.Fatalf("Cannot read directory, err: %s", err)
	}

	rgxManifest := regexp.MustCompile(`.*\.manifest`)

	for _, file := range files {
		// just copy all manifests
		if rgxManifest.MatchString(file.Name()) {
			Copy(inDir+"/"+file.Name(), outDir+"/"+file.Name())
			continue
		}

		// @todo add support for sliced tables - simply iterate in this subdirectory
		if file.IsDir() {
			continue
		}

		os.Mkdir(outDir+"/"+file.Name(), 0755)

		t = time.Now()

		split(inDir+"/"+file.Name(), outDir+"/"+file.Name(), true)

		log.Printf("file %s split took %s", file.Name(), time.Since(t))
	}
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
