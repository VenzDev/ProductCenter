package main

import (
	"context"
	"time"

	"github.com/mailgun/mailgun-go/v4"
)

// Mailer is implemented by anything that can send an email, so tests can
// swap in a fake instead of calling the real Mailgun API.
type Mailer interface {
	Send(to, subject, body string) (string, error)
}

type MailgunMailer struct {
	client mailgun.Mailgun
	sender string
}

func NewMailgunMailer(domain, apiKey, sender string) *MailgunMailer {
	return &MailgunMailer{
		client: mailgun.NewMailgun(domain, apiKey),
		sender: sender,
	}
}

func (m *MailgunMailer) Send(to, subject, body string) (string, error) {
	message := m.client.NewMessage(m.sender, subject, body, to)

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	_, id, err := m.client.Send(ctx, message)
	return id, err
}
