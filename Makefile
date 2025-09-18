SHELL := /bin/sh
c = docker compose
.PHONY: up down logs ps psql seed curl list

up:      ; $(c) up -d --build
down:    ; $(c) down
logs:    ; $(c) logs -f php-fpm
ps:      ; $(c) ps
psql:    ; $(c) exec -it postgres psql -U app -d app
seed:    ; $(c) exec -T postgres psql -U app -d app -f /docker-entrypoint-initdb.d/001_init.sql
curl:    ; curl -s http://localhost:8080/health | jq .
list:    ; curl -s http://localhost:8080/tasks | jq .
