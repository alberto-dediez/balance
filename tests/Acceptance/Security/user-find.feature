Feature:
    I want to make sure that the system is allow to return info about an user

    Scenario: As an admin, I can view users by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a GET request to "/api/users/find" with body: 
        """
        {
            "username": "user@dediez.software"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "user_id": "1123-0136-456544465464-465446566",
            "username": "user@dediez.software",
            "account_id": "123"
        }
        """