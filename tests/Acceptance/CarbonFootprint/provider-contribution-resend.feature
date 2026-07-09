Feature:
    I want to make sure that the owner can resend the notification email for a provider contribution

    Scenario: As an owner, I can resend the notification for a pending contribution
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        When I click on link "Reenviar"
        Then I am redirected to route "events_carbon_footprint_providers"
        Then the response status should be 200

    Scenario: A resend request without a valid CSRF token is refused
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        Given provider contribution "111" is ignored
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/providers/111/resend"
        Then the response status should be 403

    Scenario: As a user, I can't resend the notification
        Given account "MyCompany" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/333/carbon-footprint/providers/111/resend"
        Then the response status should be 403
