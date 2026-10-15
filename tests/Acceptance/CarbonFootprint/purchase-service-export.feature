Feature:
    Ability to export purchase services as spreadsheet files

    Scenario: As an admin I can access the purchase services export screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/purchase_services/export"
        Then the response status should be 200

    Scenario: As a normal user I cannot access the purchase services export screen
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/purchase_services/export"
        Then the response status should be 403

    Scenario: As an admin I can download a completed purchase services exportation
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given a completed exportation with id "333" of type "purchase_services" from account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/purchase_services/export/333/download"
        Then the response status should be 200
