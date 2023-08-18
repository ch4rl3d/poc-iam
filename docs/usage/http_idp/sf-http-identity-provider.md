# POC IAM

## Usage

### SF as HTTP Identity Provider

#### Local setup

First you need to connect the gateway to sf network

Boot your local sf instance and connect gateway container:

```
$ cp -u docker-compose-sf-idp.override.yml docker-compose.override.yml
$ docker-compose down gateway && docker-compose up -d
```

#### Configuration

##### IDP server

After creating a testing security domains you need to configure a HTTP identity provider

![Alt text](./create-http-idp.png?raw=true "Create HTTP IDP")

After selecting "HTTP" you need to configure it:

![Alt text](./idp-urls.png?raw=true "IDP urls")

![Alt text](./disable-password-encoding.png?raw=true "Disable password encoding")

And save your configuration

##### Client app

Then you need to create a new application of type Single-Page App

![Alt text](./new-app.png?raw=true "New App")

Then in your app settings you need to add sf username claim in the access token:

![Alt text](./claim-mapping.png?raw=true "Claim mapping")


#### Usage

You can test your configuration with a tool like Postman by configuring an OAuth 2.0 authorization

![Alt text](./postman-config.png?raw=true "Postman configuration")


Then request a token, provide your creds and ... voila!


With this configuration you normally get a "username" claim in the token with your sf user hashid


If you get any error, check the log of the gateway or sf to debug