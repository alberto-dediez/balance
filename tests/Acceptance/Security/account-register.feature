Feature:
    I want to make sure that the API allow to register an account

    Scenario: Command security:account:register is executed without errors
        Given I am the system
        When command "security:account:register" is executed with argument "name:ACompany active:true"
        Then Is Executed Successfully
        Then I want to see "accountId" in command output
        Then I want to see "name" in command output
        Then I want to see "ACompany" in command output
        Then I want to see "Activa" in command output

    Scenario: Command security:account:register is executed without errors even with a inactive account
        Given I am the system
        When command "security:account:register" is executed with argument "name:ACompany active:false"
        Then Is Executed Successfully
        Then I want to see "Inactiva" in command output

    Scenario: As an admin, I can access to register new accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/accounts/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/register"
        When I submit a form with "name:Minotour|active:1" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Cuenta creada correctamente"
    
    Scenario: As an admin, I can register new accounts by API
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        When I send a POST request to "/api/accounts" with body:
        """
        {
            "name": "Minotour",
            "active": true
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "name": "Minotour",
            "account_id": "1123-0136-456544465464-465446566",
            "active": true
        }
        """