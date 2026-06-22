#!/bin/bash

if [ -f .env ]; then
  export $(cat .env | grep -v ^# | xargs)
fi

# Selecciona el archivo docker-compose según el entorno de la aplicación
case "$APP_ENV" in
  "local")
    COMPOSE_FILE="infrastructure/docker-compose.local.yml"
    ;;
  "dev")
    COMPOSE_FILE="infrastructure/docker-compose.dev.yml"
    ;;
  "stage")
    COMPOSE_FILE="infrastructure/docker-compose.stage.yml"
    ;;
  "prod")
    COMPOSE_FILE="infrastructure/docker-compose.prod.yml"
    ;;
  *)
    echo "Entorno de aplicación no válido. Configura APP_ENV en el archivo .env."
    exit 1
    ;;
esac

# Ejecuta docker-compose con el archivo correspondiente
docker-compose -f "$COMPOSE_FILE" up --build -d