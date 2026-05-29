Feature:
    Ability to view the history of a signature request in web

    Scenario: As admin I can access the history of an existing signature request
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "aaa-111" and account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/aaa-111/history"
        Then the response status should be 200

    Scenario: As normal user I can't access the history of a signature request
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "bbb-222" and account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests/bbb-222/history"
        Then the response status should be 403

    Scenario: Viewing history of a non-existent signature request returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/00000000-0000-0000-0000-000000000000/history"
        Then the response status should be 404
