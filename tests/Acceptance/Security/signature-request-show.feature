Feature:
    Ability to view signature request detail in web

    Scenario: As admin I can view a signature request detail
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "aaa-111" and account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/aaa-111"
        Then the response status should be 200
        Then I see a text "aaa-111"

    Scenario: As admin I can view a signed signature request detail with verification data
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signed signature request with id "bbb-222" and account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/bbb-222"
        Then the response status should be 200
        Then I see a text "bbb-222"

    Scenario: As normal user I can't view signature request detail
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "ccc-333" and account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests/ccc-333"
        Then the response status should be 403

    Scenario: Viewing a non-existent signature request returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/00000000-0000-0000-0000-000000000000"
        Then the response status should be 404
