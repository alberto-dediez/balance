Feature:
    Ability to know who each euro of a paid contribution belongs to

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists

    Scenario: The net of a contribution backed by a lot paid by the client is accrued to the account
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then contribution "C1" should be "paid"
        Then contribution "C1" should have 3 ledger entries
        Then the ledger entries of contribution "C1" should add up to its total amount
        Then the ledger balance of account "666" should be "30.00"
        Then the bioscore ledger balance should be "18.40"
        Then the unassigned ledger balance should be "0.00"

    Scenario: The net of a contribution backed by a lot paid by Bioscore is retained by Bioscore
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then the ledger balance of account "666" should be "0.00"
        Then the bioscore ledger balance should be "48.40"
        Then the ledger entries of contribution "C1" should add up to its total amount

    Scenario: Tonnes without credit backing are accrued as unassigned
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "1" tonnes exists
        Given credit purchase "2222" is validated with "1" tonnes and reference "REF-1" paid by "paid_by_client"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Then the ledger balance of account "666" should be "15.00"
        Then the unassigned ledger balance should be "15.00"
        Then the ledger entries of contribution "C1" should add up to its total amount

    Scenario: The whole net of a social project is accrued to the project
        Given social project "Pozos de agua" with id "3333" and price per tonne "20" exists
        Given project "3333" is adhered to account "666"
        Given contribution "C2" of account "666" for project "3333" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C2" succeeded
        Then contribution "C2" should have 3 ledger entries
        Then the ledger balance of project "3333" should be "30.00"
        Then the ledger balance of account "666" should be "0.00"
        Then the ledger entries of contribution "C2" should add up to its total amount

    Scenario: A contribution that is not paid leaves no trace in the ledger
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given contribution "C3" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C3" failed
        Then contribution "C3" should have 0 ledger entries

    Scenario: Validating a new lot reassigns the amount that had no credit backing
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given contribution "C4" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C4" succeeded
        Then the unassigned ledger balance should be "30.00"
        Then the ledger balance of account "666" should be "0.00"
        Given credit purchase "3333" for account "666" and project "1111" requesting "2" tonnes exists
        Given credit purchase "3333" is validated with "2" tonnes and reference "REF-9" paid by "paid_by_client"
        Then the unassigned ledger balance should be "0.00"
        Then the ledger balance of account "666" should be "30.00"
        Then the ledger entries of contribution "C4" should add up to its total amount
        Then contribution "C4" should consume "2" tonnes of credit purchase "3333"
