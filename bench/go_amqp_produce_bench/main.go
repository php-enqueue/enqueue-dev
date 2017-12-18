package main

import (
	"log"
	"os"
	"time"
	"strings"
	"github.com/streadway/amqp"
  "strconv"
	// "fmt"
)

func failOnError(err error, msg string) {
	if err != nil {
		log.Fatalf("%s: %s", msg, err)
	}
}

func main() {
  bodySize, err := strconv.Atoi(os.Args[1])
	failOnError(err, "Cannot parse body size argument. Should be int")

	conn, err := amqp.Dial(os.Getenv("AMQP_DSN"))
	failOnError(err, "Failed to connect to RabbitMQ")
	defer conn.Close()

	ch, err := conn.Channel()
	failOnError(err, "Failed to open a channel")
	defer ch.Close()

	q, err := ch.QueueDeclare(
		"go_amqp_produce_bench", // name
		false,   // durable
		false,   // delete when unused
		false,   // exclusive
		false,   // no-wait
		nil,     // arguments
	)
	failOnError(err, "Failed to declare a queue")

	_, err = ch.QueuePurge(q.Name, false)
	failOnError(err, "Failed to purge a queue")
	bodyBytes := []byte(strings.Repeat("a", bodySize))
	i := 0

  start := time.Now()
  for i < 10000 {
		err = ch.Publish(
			"",     // exchange
			q.Name, // routing key
			false,  // mandatory
			false,  // immediate
			amqp.Publishing{
				ContentType: "text/plain",
				Body: bodyBytes,
			})
	  failOnError(err, "Failed to publish a message")
    i = i + 1
	}

	elapsed := time.Since(start)
  log.Printf("Produce took %s", elapsed)
}
