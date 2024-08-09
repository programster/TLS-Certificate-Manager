TLS Certificate Manager
=======================
This is a repository for storing and retrieving TLS certificates. This allows an administrator
to manage the TLS certificates, and create auth tokens that have the ability to retrieve certain
certificate(s). This way, one can configure other servers or services to have the ability to 
retrieve the certificates that they are entitled to.


## Getting Started
Create a `.env` file from the `.env.example` example provided. If you are *not* using a proxy
for TLS certificate termination, then make sure you set `SSL_ENABLED=1`, and place the certificate
for this service in `ssl/fullchain.pem` and the private key in `ssl/private.pem`. If you do not
provide certificates, but have set `SSL_ENABLED=1`, then this service will automatically generate
some self-signed certificates.

Once all the above configuration has been done, simply build and run the service:

```bash
docker compose build
docker compose up
```

## Usage
When your service needs to fetch its certificates, simply have it send an HTTP GET request to this
service with it's bearer token. Use tools like [jq](https://www.baeldung.com/linux/jq-command-json) 
in order to output just the certifiate or private key that you want. E.g. the following would 
output the fullchain.pem file:

```bash
curl 'https://cert-manager.mydomain.com/api/certs/9bdc5cba-45f3-4fc6-ac8d-1af46af07752' \
  --header 'Authorization: Bearer xxxxxxxxxxxxxxxxxxxxx' \
  | jq -r .fullchain > fullchain.pem
```

... and the following would produce the private key
```bash
curl 'https://cert-manager.mydomain.com/api/certs/9bdc5cba-45f3-4fc6-ac8d-1af46af07752' \
  --header 'Authorization: Bearer xxxxxxxxxxxxxxxxxxxxx' \
  | jq -r .private_key > privkey.pem
```

## Massive Caveat
At the moment this tool does not itself generate the TLS certificates, but relies on you POSTing 
them to the /api/certs endpoint.

