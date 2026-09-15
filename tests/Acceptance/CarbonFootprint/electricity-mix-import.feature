Feature:
    Ability to import electricity mixes with their emission factors from external platforms

    Scenario: As a normal user I can't access to import electricity mixes
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/electricity_mixes/import"
        Then the response status should be 403

    Scenario: As an admin I can access the electricity mixes importer
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can pair fields in an electricity mixes importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/pair-fields"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can translate fields in an electricity mixes importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        Given importation "123" has paired fields "nombre:country"
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/translations"
        Then the response status should be 200
        Then I see a form

    Scenario: If an electricity mixes importation doesn't have paired fields it cannot be translated
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/translations"
        Then the response status should be 302
        Then I am redirected to "/es/electricity_mixes/import/123/assign-values"

    Scenario: As an admin I can assign default values in an electricity mixes importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/assign-values"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can finish an electricity mixes importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/finish"
        Then the response status should be 200

    Scenario: As an admin I can view an executed electricity mixes importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an executed importation with id "123" of type "electricity_mixes" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/electricity_mixes/import/123/results"
        Then the response status should be 200
