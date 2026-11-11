Feature:
    Ability for admins to filter the credits list by available tonnes

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given account "OtherCompany" with id "999" exists

    Scenario: Fully compensated credits are hidden by default
        Given credit purchase "2222" for account "666" and project "1111" requesting "8" tonnes exists
        Given credit purchase "2222" is validated with "8" tonnes and reference "REF-SPENT"
        Given contribution "C1" of account "666" for project "1111" with "8" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given credit purchase "3333" for account "999" and project "1111" requesting "10" tonnes exists
        Given credit purchase "3333" is validated with "10" tonnes and reference "REF-FREE"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I see a form field "[withAvailableTonnes]" with value "1"
        Then I see a text "REF-FREE"
        Then I do not see a text "REF-SPENT"

    Scenario: Fully transferred credits are hidden by default
        Given credit purchase "2222" for account "666" and project "1111" requesting "100" tonnes exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-1" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[999]" and button "Transferir crédito"
        When I follow redirect
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I do not see a text '/es/admin/credits/2222"'
        Then I see a text "lote 2"

    Scenario: A partially compensated credit is still visible
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-PARTIAL"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I see a text "REF-PARTIAL"

    Scenario: Pending requests are still visible and rejected ones are hidden
        Given credit purchase "5555" for account "666" and project "1111" requesting "5" tonnes exists
        Given credit purchase "6666" for account "666" and project "1111" requesting "5" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/6666/reject"
        When I submit a form with "reason:Sin disponibilidad" and button "Rechazar solicitud"
        When I follow redirect
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I see a text "/es/admin/credits/5555/validate"
        Then I see a table with "2" rows
        When I filter the admin credits list by available tonnes "no"
        Then the response status should be 200
        Then I do not see a text "/es/admin/credits/5555/validate"
        Then I see a table with "2" rows

    Scenario: As an admin I can list only the exhausted credits
        Given credit purchase "2222" for account "666" and project "1111" requesting "8" tonnes exists
        Given credit purchase "2222" is validated with "8" tonnes and reference "REF-SPENT"
        Given contribution "C1" of account "666" for project "1111" with "8" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given credit purchase "3333" for account "999" and project "1111" requesting "10" tonnes exists
        Given credit purchase "3333" is validated with "10" tonnes and reference "REF-FREE"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        When I filter the admin credits list by available tonnes "no"
        Then the response status should be 200
        Then I see a text "REF-SPENT"
        Then I do not see a text "REF-FREE"

    Scenario: As an admin I can list all the credits
        Given credit purchase "2222" for account "666" and project "1111" requesting "8" tonnes exists
        Given credit purchase "2222" is validated with "8" tonnes and reference "REF-SPENT"
        Given contribution "C1" of account "666" for project "1111" with "8" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given credit purchase "3333" for account "999" and project "1111" requesting "10" tonnes exists
        Given credit purchase "3333" is validated with "10" tonnes and reference "REF-FREE"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        When I filter the admin credits list by available tonnes "all"
        Then the response status should be 200
        Then I see a text "REF-SPENT"
        Then I see a text "REF-FREE"

    Scenario: The chosen filter is kept when going back to the credits list
        Given credit purchase "2222" for account "666" and project "1111" requesting "8" tonnes exists
        Given credit purchase "2222" is validated with "8" tonnes and reference "REF-SPENT"
        Given contribution "C1" of account "666" for project "1111" with "8" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given credit purchase "3333" for account "999" and project "1111" requesting "10" tonnes exists
        Given credit purchase "3333" is validated with "10" tonnes and reference "REF-FREE"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        When I filter the admin credits list by available tonnes "all"
        When I go to "/es/admin/credits"
        Then the response status should be 200
        Then I see a text "REF-SPENT"
        Then I see a text "REF-FREE"

    Scenario: Resetting the filter goes back to the credits with available tonnes
        Given credit purchase "2222" for account "666" and project "1111" requesting "8" tonnes exists
        Given credit purchase "2222" is validated with "8" tonnes and reference "REF-SPENT"
        Given contribution "C1" of account "666" for project "1111" with "8" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded
        Given credit purchase "3333" for account "999" and project "1111" requesting "10" tonnes exists
        Given credit purchase "3333" is validated with "10" tonnes and reference "REF-FREE"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        When I filter the admin credits list by available tonnes "all"
        Then I see a text "REF-SPENT"
        When I submit a form with "" and button "Resetear filtro"
        Then the response status should be 200
        Then I see a text "REF-FREE"
        Then I do not see a text "REF-SPENT"
