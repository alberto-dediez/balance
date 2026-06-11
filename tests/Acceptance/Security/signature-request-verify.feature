Feature:
    Public signature verification endpoint

    Scenario: Anyone can access the verify page for an existing request
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "hhh-888" and account "mycompany" exists
        When I go to "/es/sign/verify/hhh-888"
        Then the response status should be 200
        Then I see a text "hhh-888"

    Scenario: Verify page shows not-signed message for pending requests
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "iii-999" and account "mycompany" exists
        When I go to "/es/sign/verify/iii-999"
        Then the response status should be 200
        Then I see a text "aún no ha sido firmado"

    Scenario: Verify page shows signature data for signed requests
        Given account "MyCompany" with id "mycompany" exists
        Given signed signature request with id "jjj-000" and account "mycompany" exists
        When I go to "/es/sign/verify/jjj-000"
        Then the response status should be 200
        Then I see a text "jjj-000"

    Scenario: Verify page returns 404 for non-existent request
        When I go to "/es/sign/verify/00000000-0000-0000-0000-000000000000"
        Then the response status should be 404
