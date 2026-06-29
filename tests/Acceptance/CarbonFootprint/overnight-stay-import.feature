Feature:
    Ability to import overnight stays with their emission factors from external platforms

    Scenario: As a normal user I can't access to import overnight stays
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/overnight_stays/import"
        Then the response status should be 403

    Scenario: As an admin I can access the overnight stays importer
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can pair fields in an overnight stays importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "overnight_stays" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/pair-fields"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can translate fields in an overnight stays importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "overnight_stays" from account "mycompany" exists
        Given importation "123" has paired fields "nombre:name"
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/translations"
        Then the response status should be 200
        Then I see a form

    Scenario: If an overnight stays importation doesn't have paired fields it cannot be translated
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "overnight_stays" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/translations"
        Then the response status should be 302
        Then I am redirected to "/es/overnight_stays/import/123/assign-values"

    Scenario: As an admin I can assign default values in an overnight stays importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "overnight_stays" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/assign-values"
        Then the response status should be 200
        Then I see a form

    Scenario: As an admin I can finish an overnight stays importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an importation with id "123" of type "overnight_stays" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/finish"
        Then the response status should be 200

    Scenario: As an admin I can view an executed overnight stays importation
        Given account "MyCompany" with id "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given an executed importation with id "123" of type "overnight_stays" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/overnight_stays/import/123/results"
        Then the response status should be 200
