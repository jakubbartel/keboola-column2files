FROM golang:1.10

COPY . /usr/local/go/src/github.com/jakubbartel/keboola-split-by-value-processor

WORKDIR /usr/local/go/src/github.com/jakubbartel/keboola-split-by-value-processor

RUN go build -o splitbyvalueprocessor .

CMD ["./splitbyvalueprocessor"]
