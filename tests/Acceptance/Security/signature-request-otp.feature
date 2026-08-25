Feature:
    OTP identity verification before signing a document

    Scenario: A valid token redirects to OTP page when not verified
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-111" and account "mycompany" and token "otp-test-token-aaa" and SMS channel exists
        When I go to "/es/sign/otp-test-token-aaa"
        Then the response status should be 302
        Then I am redirected to "/es/sign/otp-test-token-aaa/otp"

    Scenario: The OTP page shows a verification form
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-222" and account "mycompany" and token "otp-test-token-bbb" and SMS channel exists
        When I go to "/es/sign/otp-test-token-bbb/otp"
        Then the response status should be 200
        Then I see a text "Verificar"

    Scenario: A correct OTP code allows access to the signing page
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-333" and account "mycompany" and token "otp-test-token-ccc" and SMS channel exists
        When I go to "/es/sign/otp-test-token-ccc/otp"
        When I submit the OTP from the last SMS to "/es/sign/otp-test-token-ccc/otp"
        Then the response status should be 302
        Then I am redirected to "/es/sign/otp-test-token-ccc"
        Then the response status should be 200

    Scenario: An incorrect OTP code shows an error message
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-444" and account "mycompany" and token "otp-test-token-ddd" and SMS channel exists
        When I go to "/es/sign/otp-test-token-ddd/otp"
        When I submit a wrong OTP to "/es/sign/otp-test-token-ddd/otp"
        Then the response status should be 200
        Then I see a text "Código incorrecto"
        Then I see a text "Te quedan 2 intentos."

    Scenario: After three wrong attempts the OTP is locked
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-555" and account "mycompany" and token "otp-test-token-eee" and SMS channel exists
        When I go to "/es/sign/otp-test-token-eee/otp"
        When I submit a wrong OTP to "/es/sign/otp-test-token-eee/otp"
        When I submit a wrong OTP to "/es/sign/otp-test-token-eee/otp"
        When I submit a wrong OTP to "/es/sign/otp-test-token-eee/otp"
        Then the response status should be 200
        Then I see a text "máximo de intentos"

    Scenario: An invalid token returns 404 on OTP page
        When I go to "/es/sign/this-token-does-not-exist/otp"
        Then the response status should be 404

    Scenario: Resending OTP shows confirmation and keeps OTP form
        Given account "MyCompany" with id "mycompany" exists
        Given signature request with id "otp-666" and account "mycompany" and token "otp-test-token-fff" and SMS channel exists
        When I go to "/es/sign/otp-test-token-fff/otp"
        When I resend the OTP to "/es/sign/otp-test-token-fff/otp"
        Then the response status should be 200
        Then I see a text "Verificar"
