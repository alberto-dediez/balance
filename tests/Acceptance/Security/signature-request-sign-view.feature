Feature:
    Public signing page accessible via token link

    Scenario: A valid token shows the signing page
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "fff-666" and account "mycompany" and token "valid-test-token-abc" exists
        When I go to "/sign/valid-test-token-abc"
        Then the response status should be 200
        Then I see a form

    Scenario: An invalid token returns 404
        When I go to "/sign/this-token-does-not-exist"
        Then the response status should be 404

    Scenario: A valid token for a signed request shows already-signed page
        Given account "MyCompany" with id "mycompany" exists
        Given signed signature request with id "ggg-777" and account "mycompany" and token "signed-token-xyz" exists
        When I go to "/sign/signed-token-xyz"
        Then the response status should be 200
        Then I see a text "ya ha sido firmado"
