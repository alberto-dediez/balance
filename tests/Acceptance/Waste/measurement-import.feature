Feature:
    Ability to import measurements of external platforms

    Scenario: As user I can't access to import measurements
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/measurements/import"
        Then the response status should be 403

    Scenario: As owner I can access measurement importer
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import"
        Then the response status should be 200
        Then I see a form

    Scenario: As owner I can pair fields in a importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an importation with id "123" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/pair-fields"
        Then the response status should be 200
        Then I see a form

    Scenario: As owner I can translate fields in a importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an importation with id "123" from account "mycompany" exists
        Given importation "123" has paired fields "nombre:name"
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/translations"
        Then the response status should be 200
        Then I see a form

    Scenario: If an importation doesn't have paired fields it cannot be translated
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an importation with id "123" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/translations"
        Then the response status should be 302
        Then I am redirected to "/es/measurements/import/123/assign-values"

    Scenario: As owner I can assign default values in a importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an importation with id "123" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/assign-values"
        Then the response status should be 200
        Then I see a form

    Scenario: As owner I can finish a importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an importation with id "123" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/finish"
        Then the response status should be 302

    Scenario: As owner I can view an executed importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given an executed importation with id "123" from account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/measurements/import/123/results"
        Then the response status should be 200
