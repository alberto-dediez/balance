Feature:
    SSO login via API

    Scenario: A valid SSO token returns an API JWT token
        Given user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO token decodes to email "user@dediez.software"
        When I send a POST request to "/api/login/sso" with body:
        """
        {
            "username": "user@dediez.software",
            "token": "any-valid-sso-token"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "token": "any"
        }
        """

    Scenario: An invalid SSO token returns 401
        Given user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO client rejects all tokens
        When I send a POST request to "/api/login/sso" with body:
        """
        {
            "username": "user@dediez.software",
            "token": "invalid-token"
        }
        """
        Then the status code should be 401

    Scenario: An SSO token containing a different email than the username returns 401
        Given user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO token decodes to email "other@dediez.software"
        When I send a POST request to "/api/login/sso" with body:
        """
        {
            "username": "user@dediez.software",
            "token": "any-valid-sso-token"
        }
        """
        Then the status code should be 401

    Scenario: An unknown username returns 401
        Given the SSO token decodes to email "unknown@dediez.software"
        When I send a POST request to "/api/login/sso" with body:
        """
        {
            "username": "unknown@dediez.software",
            "token": "any-valid-sso-token"
        }
        """
        Then the status code should be 401
