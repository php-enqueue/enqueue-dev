How to compile:

```
cd bench/go_amqp_produce_bench
export GOPATH=`pwd`
export GOBIN=$GOPATH/bin
export GOOS=linux # enqueue/dev container is linux  
export GOARCH=amd64

go get
go build # options for prod build -ldflags '-s'
```
