FROM alpine:3.24.1
RUN apk add --no-cache openssl
CMD ["sh", "-c", "openssl req -x509 -newkey rsa:2048 -nodes -days 2 -subj /CN=localhost -addext 'subjectAltName=DNS:localhost,IP:127.0.0.1' -keyout /certificates/key.pem -out /certificates/cert.pem && chmod 644 /certificates/*.pem"]
