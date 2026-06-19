Feature:
    Ability to download signed document for a delivery log

    Scenario: As an owner, I can download the signed document for a delivery log with a completed signature
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with signed signature request "sig-req-1" at "/tmp/signed-delivery-log.pdf" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/log-1/download-signed-document"
        Then the response status should be 200

    Scenario: As an admin, I cannot download a signed document
        Given account "MyAccount" with id "my-account" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with signed signature request "sig-req-1" at "/tmp/signed-delivery-log.pdf" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/delivery-logs/log-1/download-signed-document"
        Then the response status should be 403

    Scenario: Downloading a delivery log without a signature request returns 404
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/log-1/download-signed-document"
        Then the response status should be 404

    Scenario: Downloading a delivery log with a pending (unsigned) signature request returns 404
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "log-1" of account "my-account" with pending signature request "sig-req-1" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs/log-1/download-signed-document"
        Then the response status should be 404
