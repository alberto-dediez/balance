Feature:
    Ability to review the stored exchange rates and launch their synchronization

    Scenario: As admin I see the stored exchange rates
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given exchange rate "USD" at rate "1.25" published on "2026-10-14" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/exchange-rates"
        Then the response status should be 200
        Then I see a text "Dólar estadounidense"
        Then I see a text "1,2500"
        Then I see a text "0,8000"

    Scenario: As owner I can't see the exchange rates
        Given user "owner1@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner1@dediez.software"
        When I go to "/es/admin/exchange-rates"
        Then the response status should be 403

    Scenario: As admin I enqueue the synchronization
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/exchange-rates"
        When I click on link "Sincronizar ahora"
        Then I am redirected to route "admin_exchange_rates"
        Then I see a text "Sincronización de tipos de cambio encolada"

    Scenario: Without a valid token the synchronization is refused
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/exchange-rates/sync"
        Then the response status should be 403

    Scenario: As owner I can't launch the synchronization
        Given user "owner1@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner1@dediez.software"
        When I go to "/es/admin/exchange-rates/sync"
        Then the response status should be 403
