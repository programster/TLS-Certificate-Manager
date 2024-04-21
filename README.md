TLS Certificate Manager
=======================
This is a really simple API for handing out TLS certificates to services that request them using
a basic bearer token. For now, authentication is managed through storing the hashed form of
the bearer token in the `configs/certs.yaml` file. 

## Getting Started
Enaure that each of your site's certificates are within their own folders and copy them into the
`certs` directory.

Navigate to the configs directory and create a `certs.yaml` file from the `certs.yaml.example` 
example file. Create a top-level block for each of your sites/services, and create an auth
token for each service that would need to request the certificates. The `path` needs to specify
the relative path to the folder that contains the certificates within the `certs` folder.
Use the `site/scripts/generate-auth-token.php` script to generate fresh auth tokens.

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
service with it's bearer token. E.g. with curl this would be something like:

```bash
curl 'https://cert-manager.mydomain.com/certs/9bdc5cba-45f3-4fc6-ac8d-1af46af07752' \
  --header 'Authorization: Bearer bzAyUXloKSl4SUBJe2lfeUhvcU1Iby9Y' \
  -o certs.zip
```

Then you can unzip the downloaded file with:

```bash
unzip certs.zip
```

... which should lead you to having a folder called `certs` that contains  your site's certificate
files.

## Massive Caveat
At the moment this tool does not itself generate the TLS certificates, but merely provides the 
ability to serve them up via an API with authentication. Instead, for now one has to use other 
tools to generate the certificates and simply have them copy/move the certificates into the 
relevant directory path. In the future, this tool will be able to be configured to perform the 
generation of Let's Encrypt TLS certificates through DNS challenges.

