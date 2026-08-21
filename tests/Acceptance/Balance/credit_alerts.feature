Feature:
    Ability to warn the account owners when the purchased credit of a project is running out

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"

    Scenario: The owner is warned once the consumption crosses the first threshold
        Given contribution "C1" of account "666" for project "1111" with "8.5" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the credit alerts are checked
        Then user "owner@dediez.software" should have 1 internal notifications
        Then the last internal notification of user "owner@dediez.software" should contain "Reforestación"

    Scenario: Checking again does not warn the owner twice
        Given contribution "C1" of account "666" for project "1111" with "8.5" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the credit alerts are checked
        When the credit alerts are checked
        Then user "owner@dediez.software" should have 1 internal notifications

    Scenario: Consuming the rest of the credit sends a single additional alert
        Given contribution "C1" of account "666" for project "1111" with "8.5" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the credit alerts are checked
        Given contribution "C2" of account "666" for project "1111" with "1.5" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C2" succeeded
        When the credit alerts are checked
        Then user "owner@dediez.software" should have 2 internal notifications

    Scenario: No alert is sent while the consumption stays below the first threshold
        Given contribution "C1" of account "666" for project "1111" with "5" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When the credit alerts are checked
        Then user "owner@dediez.software" should have 0 internal notifications
