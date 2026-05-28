Feature:
    I want to ungrant roles to users from command line

    Scenario: Command security:user:ungrant-role is executed without errors
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When command "security:user:ungrant-role" is executed with argument "username:admin@dediez.software role:ROLE_ADMIN"
        Then Is Executed Successfully
        Then I want to see "userId" in command output
        Then I want to see "username" in command output
        Then I want to see "roles" in command output
        Then I don't want to see "ROLE_ADMIN" in command output

    Scenario: As an admin, I can ungrant a role from a user via API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" with id "admin" of account "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a PUT request to "/api/users/owner/ungrant-role" with body:
        """
        {
            "role": "ROLE_OWNER"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "user_id": "owner",
            "username": "owner@dediez.software",
            "roles": ["ROLE_USER"]
        }
        """

    Scenario: As an owner, I can ungrant a role within my account via API
        Given account "mycompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        Given user "user@dediez.software" with role "ROLE_OWNER" with id "user" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a PUT request to "/api/users/user/ungrant-role" with body:
        """
        {
            "role": "ROLE_OWNER"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "user_id": "user",
            "username": "user@dediez.software"
        }
        """

    Scenario: Not authenticated user cannot ungrant a role via API
        When I send a PUT request to "/api/users/user/ungrant-role" with body:
        """
        {
            "role": "ROLE_OWNER"
        }
        """
        Then the status code should be 403