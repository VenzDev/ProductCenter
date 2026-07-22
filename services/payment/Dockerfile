FROM golang:1.26-alpine AS base

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

EXPOSE 8080

FROM base AS dev

RUN go install github.com/air-verse/air@latest

COPY . .

CMD ["air"]

FROM base AS build

COPY . .
RUN go build -o payment .

FROM alpine AS prod

WORKDIR /app

COPY --from=build /app/payment .

EXPOSE 8080

CMD ["./payment"]
