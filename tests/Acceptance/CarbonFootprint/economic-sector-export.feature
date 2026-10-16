Feature:
    Ability to export economic sectors as spreadsheet files

    Scenario: As an admin I can access the economic sectors export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/economic_sectors/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the economic sectors export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/economic_sectors/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed economic sectors exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "economic_sectors" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/economic_sectors/export/333/download"
        Then the response status should be 200

    Scenario: As an admin I can export the classification code of the economic sectors
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/economic_sectors/export"
        Then the response status should be 200
        Then I see a text "Código CNAE/NACE"
