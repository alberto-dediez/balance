Feature:
    Ability to list delivery logs in web

    Scenario: As an owner, I can access to list of delivery logs
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "Apples" with id "log-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/delivery-logs"
        Then the response status should be 200
        Then I see a table with 2 rows

    Scenario: As an admin, I can't access to delivery logs
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/delivery-logs"
        Then the response status should be 403
