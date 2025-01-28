TLS Certificate Manager
=======================
This is a repository for storing and retrieving TLS certificates. This allows an administrator
to manage the TLS certificates, and create auth tokens that have the ability to retrieve certain
certificate(s). This way, one can configure other servers or services to have the ability to 
retrieve the certificates that they are entitled to.

**This tool does not itself generate the TLS certificates, but relies on you POSTing 
them to the /api/certs endpoint.**




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

The easiest way to use this service is to use the 
[PHP SDK](https://github.com/programster/package-tls-cert-manager-sdk).
Failing that, I have provided some examples below using cURL in a terminal.


### Creating New Certificates
When you wish to create a new certificate bundle, you need to POST it. Certificate bundles
are only created once, and then can only be updated.
Once you have created the certificate bundle, you can update it with PATCH requests.

```bash
TOKEN_ID="9e115392-961c-4d1e-929f-f11cf5f69dd4"
SECRET="mySecurityToken"
ENCODED_SECRET=$(echo -n $SECRET | base64)
DOMAIN="cert-manager.mydomain.com"
JSON_FILEPATH="/path/to/json/file.json"

curl \
  -u "$TOKEN_ID:$ENCODED_SECRET" \
  --request PATCH \
  --header 'Content-Type: application/json' \
  "https://$DOMAIN/api/certs/$CERT_ID" \
  --data @$JSON_FILEPATH
```

Please note that to be able to create certificates, you need either be using an admin token, 
or the `CertificateCreator` token level. Tokens that create certificates are 
automatically assigned to that certificate to allow future updates.


### Updating Certificates
Certificates are updated with PATCH requests like so:

Please note that to be able to update certificates, you need either the admin token level,
or have the `CertificateCreator` token level, and have been assigned to that certificate
(certificates the creators make, are automatically assigned to them). 


### Fetching Certificates
When your service needs to fetch its certificates, simply have it send an HTTP GET request to this
service with it's bearer token. Use tools like [jq](https://www.baeldung.com/linux/jq-command-json) 
in order to output just the certificate or private key that you want. E.g. the following would 
output the fullchain.pem file:

```bash
CERT_ID="9bdc5cba-45f3-4fc6-ac8d-1af46af07752"
TOKEN_ID="9e115392-961c-4d1e-929f-f11cf5f69dd4"
SECRET="mySecurityToken"
ENCODED_SECRET=$(echo -n $SECRET | base64)
DOMAIN="cert-manager.mydomain.com"

curl \
  -u "$TOKEN_ID:$ENCODED_SECRET" \
  --header 'Content-Type: application/json' \
  "https://$DOMAIN/api/certs/$CERT_ID" \
  | jq -r .fullchain > fullchain.pem
```

... and the following would produce the private key
```bash
CERT_ID="9bdc5cba-45f3-4fc6-ac8d-1af46af07752"
TOKEN_ID="9e115392-961c-4d1e-929f-f11cf5f69dd4"
SECRET="mySecurityToken"
ENCODED_SECRET=$(echo -n $SECRET | base64)
DOMAIN="cert-manager.mydomain.com"

curl "https://$DOMAIN/api/certs/$CERT_ID" \
  -u "$TOKEN_ID:$ENCODED_SECRET" \
  --header 'Content-Type: application/json' \
  | jq -r .private_key > privkey.pem
```


### Deleting Certificates
```bash
TOKEN_ID="9e115392-961c-4d1e-929f-f11cf5f69dd4"
CERT_ID="9bdc5cba-45f3-4fc6-ac8d-1af46af07752"
SECRET="mySecurityToken"
ENCODED_SECRET=$(echo -n $SECRET | base64)
DOMAIN="cert-manager.mydomain.com"

curl \
  -u "$TOKEN_ID:$ENCODED_SECRET" \
  --request "DELETE" \
  --header 'Content-Type: application/json' \
  "https://$DOMAIN/api/certs/$CERT_ID"
```

To be able to delete certificates, you need either the admin token level,
or have the `CertificateCreator` token level, and have been assigned to that certificate
(certificates the creators make, are automatically assigned to them).


### Create Auth Token
```bash
TOKEN_ID="9e115392-961c-4d1e-929f-f11cf5f69dd4"
SECRET="mySuperSecurePasswordGoesHere"
CERT_ID="9e0f98bb-e9b7-461a-9030-ca6aa6eb84a8"
ENCODED_SECRET=$(echo -n $SECRET | base64)
DOMAIN="certs.mydomain.com"

curl \
  -u "$TOKEN_ID:$ENCODED_SECRET" \
  --request "POST" \
  --header 'Content-Type: application/json' \
  --data '{"name": "authToken20", "description": "my creator auth token", "level": 2}' \
  "https://$DOMAIN/api/auth-tokens" \
  | jq
```

To be able to delete certificates, you need either the admin token level,
or have the `CertificateCreator` token level, and have been assigned to that certificate
(certificates the creators make, are automatically assigned to them).



