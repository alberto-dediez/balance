Feature:
    I want to make sure that the software allows cancelling the signature process for delivery logs

    Scenario: As an owner, I can access the cancel signature process page
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with pending signature request "sig-1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/cancel-signature-process/sig-1"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can cancel the signature process
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with pending signature request "sig-1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/cancel-signature-process/sig-1"
        When I submit a form with "" and button "Confirmar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha cancelado el proceso de firma correctamente"

    Scenario: As a user, I can also cancel the signature process for delivery logs
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with pending signature request "sig-1" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/delivery-logs/cancel-signature-process/sig-1"
        When I submit a form with "" and button "Confirmar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha cancelado el proceso de firma correctamente"

    Scenario: Accessing with an unknown signature request id returns not found
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/cancel-signature-process/unknown-sig"
        Then the response status should be 404
