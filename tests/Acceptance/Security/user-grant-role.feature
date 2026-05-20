Feature:
        I want to grant roles to users from command line

        Scenario: Command security:user:grant-role is executed without errors
            Given I am the system
            Given user "employee@dediez.software" with role "ROLE_USER" exists
            When command "security:user:grant-role" is executed with argument "username:employee@dediez.software role:ROLE_ADMIN"
            Then Is Executed Successfully
            Then I want to see "userId" in command output
            Then I want to see "username" in command output
            Then I want to see "roles" in command output
            Then I want to see "ROLE_ADMIN" in command output

        Scenario: As an admin, I can grant a role to a user via API
            Given user "admin@dediez.software" with role "ROLE_ADMIN" with id "admin" of account "mycompany" exists
            Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
            When I am authenticated in API as "admin@dediez.software"
            And I send a PUT request to "/api/users/user/grant-role" with body:
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
                "username": "user@dediez.software",
                "roles": ["ROLE_USER", "ROLE_OWNER"]
            }
            """

        Scenario: As an owner, I can grant a role within my account via API
            Given account "mycompany" with id "mycompany" exists
            Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
            Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
            When I am authenticated in API as "owner@dediez.software"
            And I send a PUT request to "/api/users/user/grant-role" with body:
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

        Scenario: Not authenticated user cannot grant a role via API
            When I send a PUT request to "/api/users/user/grant-role" with body:
            """
            {
                "role": "ROLE_OWNER"
            }
            """
            Then the status code should be 403
