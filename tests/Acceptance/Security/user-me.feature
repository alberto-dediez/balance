Feature:
        Access to user logged info using api

        Scenario: Ask api about logged user
                Given account "MyCompany" with id "mycompany" exists
                Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                When I am authenticated in API as "user@dediez.software"
                When I send a GET request to "/api/users/me"
                Then the status code should be 200
                And the response JSON should be equal to:
                """
                {
                        "username": "user@dediez.software",
                        "account": "MyCompany"
                }
                """

        Scenario: User without account
                Given user "user@dediez.software" with role "ROLE_USER" exists
                When I am authenticated in API as "user@dediez.software"
                When I send a GET request to "/api/users/me"
                Then the status code should be 200
                And the response JSON should be equal to:
                """
                {
                        "username": "user@dediez.software",
                        "account": ""
                }
                """

        Scenario: Not authenticated user
                When I send a GET request to "/api/users/me"
                Then the status code should be 403