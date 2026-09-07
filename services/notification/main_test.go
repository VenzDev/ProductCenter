package main

import (
	"bytes"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
)

type fakeMailer struct {
	err error
}

func (f *fakeMailer) Send(to, subject, body string) (string, error) {
	if f.err != nil {
		return "", f.err
	}
	return "fake-message-id", nil
}

func TestHealthEndpoint(t *testing.T) {
	req := httptest.NewRequest(http.MethodGet, "/health", nil)
	w := httptest.NewRecorder()

	setupRouter(&fakeMailer{}).ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected status 200, got %d", w.Code)
	}
}

func TestTestEmailEndpoint_Success(t *testing.T) {
	body, _ := json.Marshal(gin.H{"to": "user@example.com"})
	req := httptest.NewRequest(http.MethodPost, "/api/v1/notifications/test-email", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()

	setupRouter(&fakeMailer{}).ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected status 200, got %d: %s", w.Code, w.Body.String())
	}
}

func TestTestEmailEndpoint_InvalidAddress(t *testing.T) {
	body, _ := json.Marshal(gin.H{"to": "not-an-email"})
	req := httptest.NewRequest(http.MethodPost, "/api/v1/notifications/test-email", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()

	setupRouter(&fakeMailer{}).ServeHTTP(w, req)

	if w.Code != http.StatusBadRequest {
		t.Fatalf("expected status 400, got %d", w.Code)
	}
}

func TestTestEmailEndpoint_MailerError(t *testing.T) {
	body, _ := json.Marshal(gin.H{"to": "user@example.com"})
	req := httptest.NewRequest(http.MethodPost, "/api/v1/notifications/test-email", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()

	setupRouter(&fakeMailer{err: errors.New("mailgun down")}).ServeHTTP(w, req)

	if w.Code != http.StatusBadGateway {
		t.Fatalf("expected status 502, got %d", w.Code)
	}
}
