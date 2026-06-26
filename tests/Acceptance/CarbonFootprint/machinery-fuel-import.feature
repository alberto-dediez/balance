Feature:
    Ability to import machinery fuels with their emission factors from external platforms

    Scenario: As a normal user I can't access to import machinery fuels
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/machinery_fuels/import"
        Then the response status should be 403

    Scenario: As an admin I can access the machinery fuels importer
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/machinery_fuels/import"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can pair fields in a machinery fuels importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "machinery_fuels" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/machinery_fuels/import/123/pair-fields"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can assign default values in a machinery fuels importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "machinery_fuels" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/machinery_fuels/import/123/assign-values"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can finish a machinery fuels importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "machinery_fuels" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/machinery_fuels/import/123/finish"
        Then the response status should be 200

    Scenario: As an admin I can view an executed machinery fuels importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an executed importation with id "123" of type "machinery_fuels" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/machinery_fuels/import/123/results"
        Then the response status should be 200
