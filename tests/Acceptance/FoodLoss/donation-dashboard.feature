Feature:
    Ability to see the donation dashboard in web

    Scenario: As an owner, I can access the donation dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "delivery-1" of account "my-account" exists
        Given delivery log "vegetables" with id "delivery-2" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/donations/dashboard"
        Then the response status should be 200
        Then I see a text "Entregas registradas"
        Then I see a text "Estado de convenios"

    Scenario: As an owner, I cannot see delivery logs of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given delivery log "fruit" with id "delivery-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/donations/dashboard"
        Then the response status should be 200
        Then I see a text "No hay entregas registradas para los filtros seleccionados"

    Scenario: As a normal user, I can't access the donation dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/donations/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the donation dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/donations/dashboard"
        Then the response status should be 403
