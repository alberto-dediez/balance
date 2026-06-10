Feature:
    Ability to download signed document from admin

    Scenario: Download returns 404 when the signature request has no signed document
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "ddd-444" and account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/ddd-444/download"
        Then the response status should be 404

    Scenario: As normal user I can't download signed documents
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signed signature request with id "eee-555" and account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests/eee-555/download"
        Then the response status should be 403
