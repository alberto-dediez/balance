Feature:
    Access to api login

    Scenario: Api login returns token
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "apassword"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NDk1NDQ5MzEsImV4cCI6MTc0OTU0ODUzMSwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6ImFsYmVydG9AZGVkaWV6LnNvZnR3YXJlIn0.Ur1oHFNlP0dRxtKOr8uwpwgoeT5YFM-uGQUVvpUi0_MRWzwFjJXRYk2V8vntNEUmpkSmWkCiyjRuNmGOMFCSlk_JDMgzBhYMGUoFdr0ndVS-nDdHCuqkknhG54oNfb2nyb9rdNXr3zYQ4TYQiP3qnHkz09184ZM3bnLy5oUIQnmnGT5l6V340AAzjPxW_q8-NdSymhFpxFEu5daQ2rljvkpz8NGYoAUX6Y7tUOybyXAg9heEtHUtSQkLFKNikWLi4BEU4qrceBsEaCMgtxzYefFuv1n3KhjRmV8p_7HR8wMDYXq5UvsRnJEmkxTAHo6YBwuIL4AS0qNddM_ZPTzgFg"
        }
        """

    Scenario: Given an expired token, we need to return a known error
        Given user "user@dediez.software" with role "ROLE_USER" exists
        Given I have an expired token for "user@dediez.software"
        When I send a GET request to "/api/users/me"
        Then the status code should be 401

    Scenario: Api login with a wrong password returns 401
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "wrong-password"
        }
        """
        Then the status code should be 401

    Scenario: Api login with an api token in the password field returns token
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_valid_token_of_user_0000000000000000000000"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9"
        }
        """

    Scenario: Api login with a user without account and an api token returns token
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "admin@dediez.software" has an api token "hyd_valid_token_of_admin_000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "admin@dediez.software",
            "password": "hyd_valid_token_of_admin_000000000000000000000"
        }
        """
        Then the status code should be 200

    Scenario: Api login with a revoked api token returns 401
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has a revoked api token "hyd_revoked_token_000000000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_revoked_token_000000000000000000000000000"
        }
        """
        Then the status code should be 401

    Scenario: Api login with an expired api token returns 401
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an expired api token "hyd_expired_token_000000000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_expired_token_000000000000000000000000000"
        }
        """
        Then the status code should be 401

    Scenario: Api login with an api token of another user returns 401
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "other@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "other@dediez.software" has an api token "hyd_token_of_other_user_00000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_token_of_other_user_00000000000000000000"
        }
        """
        Then the status code should be 401

    Scenario: Api login with an api token of an account without permission returns 401
        Given account "MyCompany" with id "333" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_token_without_permission_000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_token_without_permission_000000000000000"
        }
        """
        Then the status code should be 401

    Scenario: Api login with the password still works for a user with api tokens
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "apassword"
        }
        """
        Then the status code should be 200

    Scenario: A JWT obtained with an api token stops working when the token is revoked
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I am authenticated in API as "user@dediez.software" with api token "hyd_valid_token_of_user_0000000000000000000000"
        When I send a GET request to "/api/users/me"
        Then the status code should be 200
        Given the api token "hyd_valid_token_of_user_0000000000000000000000" is revoked
        When I send a GET request to "/api/users/me"
        Then the status code should be 401

    Scenario: A JWT returned by the api login with an api token stops working when the token is revoked
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_valid_token_of_user_0000000000000000000000"
        }
        """
        Then the status code should be 200
        When I use the token of the response as bearer
        When I send a GET request to "/api/users/me"
        Then the status code should be 200
        Given the api token "hyd_valid_token_of_user_0000000000000000000000" is revoked
        When I send a GET request to "/api/users/me"
        Then the status code should be 401

    Scenario: A JWT returned by the api login with the password keeps working when the api token is revoked
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "apassword"
        }
        """
        Then the status code should be 200
        When I use the token of the response as bearer
        When I send a GET request to "/api/users/me"
        Then the status code should be 200
        Given the api token "hyd_valid_token_of_user_0000000000000000000000" is revoked
        When I send a GET request to "/api/users/me"
        Then the status code should be 200

    Scenario: A JWT obtained with the password keeps working when the user has revoked api tokens
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has a revoked api token "hyd_revoked_token_000000000000000000000000000" named "Integration"
        When I am authenticated in API as "user@dediez.software"
        When I send a GET request to "/api/users/me"
        Then the status code should be 200

    Scenario: Api tokens are suspended while the account does not allow them
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_valid_token_of_user_0000000000000000000000" named "Integration"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/edit/333"
        When I submit a form with "name:MyCompany|active:1|apiTokensEnabled:0" and button "Guardar y salir"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_valid_token_of_user_0000000000000000000000"
        }
        """
        Then the status code should be 401
        When I go to "/es/admin/accounts/edit/333"
        When I submit a form with "name:MyCompany|active:1|apiTokensEnabled:1" and button "Guardar y salir"
        When I send a POST request to "/api/login" with body:
        """
        {
            "username": "user@dediez.software",
            "password": "hyd_valid_token_of_user_0000000000000000000000"
        }
        """
        Then the status code should be 200
