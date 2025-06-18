Feature: CORS support for api

    Scenario: Valid CORS request to /api/login from allowed origin
        When I make a preflight CORS request to "/api/login" from "https://valid-url.com"
        Then the status code should be 200
        And the response header "Access-Control-Allow-Origin" should be "https://valid-url.com"
        And the response header "Access-Control-Allow-Methods" should exist