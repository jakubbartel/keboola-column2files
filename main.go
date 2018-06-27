package main

import (
	"encoding/json"
	"io"
	"io/ioutil"
	"log"
	"os"
	"regexp"
	"time"
)

type Manifest struct {
	Columns []string `json:"columns"`
}

type Config struct {
	Parameters ConfigParameters `json:"parameters"`
}

type ConfigParameters struct {
	ColumnName string `json:"column_name"`
}

func main() {
	var t time.Time

	dataPath, ok := os.LookupEnv("KBC_DATADIR")
	if !ok {
		dataPath = "/data/"
	}

	cb, err := ioutil.ReadFile(dataPath + "/config.json")
	if err != nil {
		log.Fatalf("cannot read config, error: %s", err)
	}

	conf := Config{}
	err = json.Unmarshal(cb, &conf)
	if err != nil {
		log.Fatalf("cannot parse config, error: %s", err)
	}

	inDir := dataPath + "in/tables"
	outDir := dataPath + "out/tables"

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

		if _, err := os.Stat(inDir + "/" + file.Name() + ".manifest"); os.IsNotExist(err) {
			log.Printf("skipping file %s without manifest", file.Name())
			continue
		}

		mb, err := ioutil.ReadFile(inDir + "/" + file.Name() + ".manifest")
		if err != nil {
			log.Printf("cannot read files %s manifest, skipping", file.Name())
			continue
		}

		m := Manifest{}
		err = json.Unmarshal(mb, &m)
		if err != nil {
			log.Printf("cannot parse columns from files %s manifest, skipping", file.Name())
			continue
		}

		columnIndex := -1

		for i, col := range m.Columns {
			if col == conf.Parameters.ColumnName {
				columnIndex = i
				break
			}
		}

		if columnIndex == -1 {
			log.Printf("cannot find column %s in manifest, skipping", conf.Parameters.ColumnName)
			continue
		} else {
			log.Printf("splitting by column index %d", columnIndex)
		}

		// @todo add support for sliced tables - simply iterate in this subdirectory
		if file.IsDir() {
			continue
		}

		os.Mkdir(outDir+"/"+file.Name(), 0755)

		t = time.Now()

		split(inDir+"/"+file.Name(), outDir+"/"+file.Name(), columnIndex, true)

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
