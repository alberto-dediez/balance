Feature:
    I want to make sure that the API allows to manage the consultant of an account

    Scenario: As an admin, I can register a consultant account by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a POST request to "/api/accounts" with body:
        """
        {
            "name": "TheConsultant",
            "active": true,
            "consultant": true
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "name": "TheConsultant",
            "account_id": "1123-0136-456544465464-465446566",
            "active": true,
            "consultant": true
        }
        """

    Scenario: As an admin, I can find an account by its name by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a GET request to "/api/accounts/find" with body:
        """
        {
            "name": "Minotour"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "name": "Minotour",
            "account_id": "1111",
            "active": true,
            "consultant": false
        }
        """

    Scenario: As an admin, I get a not found response when the account name does not exist
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a GET request to "/api/accounts/find" with body:
        """
        {
            "name": "Unknown"
        }
        """
        Then the status code should be 404

    Scenario: As an admin, I can mark an account as consultant by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "TheConsultant" with id "444" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a PUT request to "/api/accounts/444/consultant-status" with body:
        """
        {
            "consultant": true
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "account_id": "444",
            "consultant": true
        }
        """

    Scenario: As an admin, I can assign a consultant to an account by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        Given account "TheConsultant" with id "444" is a consultant
        When I am authenticated in API as "admin@dediez.software"
        When I send a PUT request to "/api/accounts/1111/consultant" with body:
        """
        {
            "consultant_account_id": "444"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "account_id": "1111",
            "consultant_account_id": "444"
        }
        """

    Scenario: As an admin, I cannot assign an account that is not a consultant
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a PUT request to "/api/accounts/1111/consultant" with body:
        """
        {
            "consultant_account_id": "2222"
        }
        """
        Then the status code should be 409

    Scenario: As an admin, I can clear the consultant of an account by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        Given account "TheConsultant" with id "444" is a consultant
        Given account "1111" has consultant "444"
        When I am authenticated in API as "admin@dediez.software"
        When I send a PUT request to "/api/accounts/1111/consultant" with body:
        """
        {
            "consultant_account_id": null
        }
        """
        Then the status code should be 200
        And the response JSON attribute "consultant_account_id" should be null

    Scenario: Not authenticated user cannot change the consultant of an account via API
        Given account "Minotour" with id "1111" exists
        When I send a PUT request to "/api/accounts/1111/consultant" with body:
        """
        {
            "consultant_account_id": null
        }
        """
        Then the status code should be 403
