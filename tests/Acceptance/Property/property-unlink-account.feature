Feature:
    I want to make sure that the system allows to unlink a property from its account

    Scenario: As an admin, I can unlink a property from its account via API
        Given account "MyCompany" with id "mycompany" exists
        Given property "Hotel" with id "333" of account "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 200
        And the response JSON attribute "account_id" should be null

    Scenario: Unlinking a property from its account keeps its operating regime
        Given account "MyCompany" with id "mycompany" exists
        Given property "Hotel" with id "333" of account "mycompany" exists
        Given property "333" has operating regime "franchised"
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 200
        And the response JSON attribute "account_id" should be null
        And property "333" should have operating regime "franchised"

    Scenario: As an owner, I can unlink a property from my own account via API
        Given account "MyCompany" with id "mycompany" exists
        Given property "Hotel" with id "333" of account "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 200
        And the response JSON attribute "account_id" should be null

    Scenario: As an owner, I cannot unlink a property from another account via API
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given property "Hotel" with id "333" of account "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 403

    Scenario: As a normal user, I cannot unlink a property from its account via API
        Given account "MyCompany" with id "mycompany" exists
        Given property "Hotel" with id "333" of account "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        When I am authenticated in API as "user1@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 403

    Scenario: Not authenticated user cannot unlink a property from its account via API
        Given property "Hotel" with id "333" exists
        When I send a "DELETE" request to "/api/es/properties/333/account"
        Then the status code should be 403

    Scenario: Unlinking a non existent property returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a "DELETE" request to "/api/es/properties/unknown/account"
        Then the status code should be 404
