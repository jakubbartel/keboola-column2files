# Keboola Split By Value Processor

Split one csv to multiple ones by distinct values in a selected column. Result will be served as a sliced table.

## Configuration

Example processor configuration - create files by values in column with name `city`:
```
{
    "definition": {
        "component": "jakub-bartel.processor-split-by-value"
    }
    "parameters": {
        "column_name": "city"
    }
}
```

Alternative example processor configuration - create files by values in the first column (index `0`):
```
{
    "definition": {
        "component": "jakub-bartel.processor-split-by-value"
    }
    "parameters": {
        "column_index": 0
    }
}
```

### Input

Csv file(s) are expected to be without header row.

```
file: weather.csv (columns: city, date, precipitation)
-----------------
"Prague","2018-09-20","25.7"
"Prague","2018-09-21","1.8"
"Berlin","2018-09-20","25.7"
"Budapest","2018-09-20","25.7"
"Berlin","2018-09-21","25.7"
"Prague","2018-09-22","13.9"
"Bratislava","2018-09-20","25.7"
```

### Output

```
file: weather.csv/Prague (columns: city, date, precipitation)
------------------------
"Prague","2018-09-20","25.7"
"Prague","2018-09-21","1.8"
"Prague","2018-09-22","13.9"
```

```
file: weather.csv/Berlin (columns: city, date, precipitation)
------------------------
"Berlin","2018-09-20","25.7"
"Berlin","2018-09-21","25.7"
```

```
file: weather.csv/Budapest (columns: city, date, precipitation)
--------------------------
"Budapest","2018-09-20","25.7"
```

```
file: weather.csv/Bratislava (columns: city, date, precipitation)
----------------------------
"Bratislava","2018-09-20","25.7"
```
