Feature:
    Ability to export merchandising items as spreadsheet files

    Scenario: As an admin I can access the merchandising items export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/merchandising_items/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the merchandising items export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/merchandising_items/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed merchandising items exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "merchandising_items" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/merchandising_items/export/333/download"
        Then the response status should be 200
