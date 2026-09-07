package main

import (
	"net/mail"
	"os"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promauto"
	"github.com/prometheus/client_golang/prometheus/promhttp"
)

var (
	httpRequestsTotal = promauto.NewCounterVec(
		prometheus.CounterOpts{
			Name: "http_requests_total",
			Help: "Total number of HTTP requests",
		},
		[]string{"method", "path", "status"},
	)
	httpRequestDuration = promauto.NewHistogramVec(
		prometheus.HistogramOpts{
			Name: "http_request_duration_seconds",
			Help: "HTTP request duration in seconds",
		},
		[]string{"method", "path"},
	)
)

func metricsMiddleware(c *gin.Context) {
	start := time.Now()
	c.Next()

	path := c.FullPath()
	if path == "" {
		path = "unmatched"
	}

	httpRequestsTotal.WithLabelValues(c.Request.Method, path, strconv.Itoa(c.Writer.Status())).Inc()
	httpRequestDuration.WithLabelValues(c.Request.Method, path).Observe(time.Since(start).Seconds())
}

func setupRouter(mailer Mailer) *gin.Engine {
	r := gin.Default()
	r.Use(metricsMiddleware)

	r.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	r.GET("/metrics", gin.WrapH(promhttp.Handler()))

	r.GET("/", func(c *gin.Context) {
		c.JSON(200, gin.H{"message": "hello world"})
	})

	r.POST("/api/v1/notifications/test-email", func(c *gin.Context) {
		var body struct {
			To string `json:"to"`
		}
		if err := c.ShouldBindJSON(&body); err != nil || body.To == "" {
			c.JSON(400, gin.H{"error": "\"to\" is required"})
			return
		}
		if _, err := mail.ParseAddress(body.To); err != nil {
			c.JSON(400, gin.H{"error": "\"to\" must be a valid email address"})
			return
		}

		id, err := mailer.Send(body.To, "Hello from notification service", "This is a test email sent via Mailgun.")
		if err != nil {
			c.JSON(502, gin.H{"error": err.Error()})
			return
		}

		c.JSON(200, gin.H{"id": id})
	})

	return r
}

func main() {
	mailer := NewMailgunMailer(os.Getenv("MAILGUN_DOMAIN"), os.Getenv("MAILGUN_API_KEY"), os.Getenv("MAILGUN_SENDER"))
	setupRouter(mailer).Run(":8080")
}
