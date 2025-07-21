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