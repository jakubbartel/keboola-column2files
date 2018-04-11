# Keboola Column To Files Processor

Split one csv to multiple ones by distinct values in a selected column.

## Configuration

Example processor configuration - create files by values in column `city`:
```
{
    "definition": {
        "component": "jakub-bartel.processor-column2files"
    }
    "parameters": {
        "column": "city"
    }
}
```

### Input

```
file: weather.csv
-----------------
city;date;precipitation
Prague;2018-09-20;25.7
Prague;2018-09-21;1.8
Berlin;2018-09-20;25.7
Budapest;2018-09-20;25.7
Berlin;2018-09-21;25.7
Prague;2018-09-22;13.9
Bratislava;2018-09-20;25.7
```

### Output

```
file: weather--Prague.csv
------------------------
city;date;precipitation
Prague;2018-09-20;25.7
Prague;2018-09-21;1.8
Prague;2018-09-22;13.9
```

```
file: weather--Berlin.csv
------------------------
city;date;precipitation
Berlin;2018-09-20;25.7
Berlin;2018-09-21;25.7
```

```
file: weather--Budapest.csv
--------------------------
city;date;precipitation
Budapest;2018-09-20;25.7
```

```
file: weather--Bratislava.csv
----------------------------
city;date;precipitation
Bratislava;2018-09-20;25.7
```
