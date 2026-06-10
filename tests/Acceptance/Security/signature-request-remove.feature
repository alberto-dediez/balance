Feature:
    I want to make sure that the system is allow to remove a signature request

    Scenario: As an admin, I can access to remove signature requests
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "333" and account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/signature-requests/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la solicitud de firma 333"

    Scenario: As normal user I can't remove signature requests
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "333" and account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/signature-requests/remove/333"
        Then the response status should be 403
