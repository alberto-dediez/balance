Feature:
    Ability to download the daily ECB exchange rates from the console

    Scenario: The ECB feed is stored as exchange rates
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the ECB feed of "2026-10-14" contains currency "USD" at rate "1.25"
        When command "carbon-footprint:exchange-rates:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then exchange rate "USD" should be "1.25" published on "2026-10-14"

    Scenario: A newer feed replaces the stored rate
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given exchange rate "USD" at rate "1.20" published on "2026-10-13" exists
        Given the ECB feed of "2026-10-14" contains currency "USD" at rate "1.25"
        When command "carbon-footprint:exchange-rates:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then exchange rate "USD" should be "1.25" published on "2026-10-14"
        Then there should be 1 exchange rate for "USD"

    Scenario: An unknown currency is reported and skipped
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the ECB feed of "2026-10-14" contains currency "XXX" at rate "2.5"
        When command "carbon-footprint:exchange-rates:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then I want to see "XXX" in command output
        Then exchange rate "XXX" should not exist

    Scenario: An unavailable feed notifies the administrators
        Given I am the system
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given the ECB feed is unavailable
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/exchange-rates"
        When command "carbon-footprint:exchange-rates:sync" is executed with argument "username:admin@dediez.software"
        Then I expect status code "0"
        Then I want to see "unavailable" in command output
        When I go to "/es/notifications"
        Then I see a text "No se pudieron descargar los tipos de cambio del BCE"
