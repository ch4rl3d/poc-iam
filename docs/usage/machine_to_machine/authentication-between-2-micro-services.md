# POC IAM

## Usage

### Authentication between 2 microservices


First create a new application of type Backend to Backend in the [IAM](http://localhost:8080/am/ui)

![Alt text](./new-app-type.png?raw=true "New app type")



Then add a name and a Client ID

![Alt text](./name-and-client-id.png?raw=true "Name and client ID")

You need also to set a few variables in `.env` file:
- the name of your domain in the var IAM_DOMAIN 
- the client id and client secret in CLIENT_ID & CLIENT_SECRET


You can find application credentials in the application settings section:

![Alt text](./application-credentials.png?raw=true "Application credentials")

After that you need to reboot your container to take new value into account:
```bash
docker-compose up -d
```


The server microservice expose an dummy api to get or create orders. You can see API documentation here [http://localhost:8000/api/docs](http://localhost:8000/api/docs). You try it out some endpoint you will get an "Unauthorized error".

The client microservice expose a cli to create an order on server microservice. Before that it will get a token from the IAM using a [client_credential](https://auth0.com/docs/get-started/authentication-and-authorization-flow/client-credentials-flow) flow.

To create an order execute:
```bash
docker-compose exec microservice_client_php bin/console app:order:create
```


If you want to fetch order list from API documentation, first you need to get an token manually:
```bash
$ export basic_auth=$(echo -n "<client_id>:<client_secret>" | base64 -w 0; echo "\n")
curl -X POST \
              http://localhost:8080/am/test/oauth/token \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H "Authorization: Basic $basic_auth" \
  -d 'grant_type=client_credentials'
```

Take the access_token in the response and paste it in with Bearer prefix in the Authorize section of API doc


![Alt text](./api-doc-auth.png?raw=true "Api doc authentication")

Then you can call the api to fetch or create orders

To see how it works see `App\Security` directory in both server and client application.