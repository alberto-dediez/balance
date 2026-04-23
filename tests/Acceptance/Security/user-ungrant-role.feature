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