Feature:
    Ability for an owner to see what they have earned and what has already been paid to them

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "999" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded

    Scenario: An owner sees their accrued balance and the lot behind it
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I see a text "Devengado"
        Then I see a text "30,00 €"
        Then I see a text "Reforestación"
        Then I see a text "REF-1"

    Scenario: An owner never sees the management fee, the VAT nor the unassigned amounts
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I do not see a text "10,00 €"
        Then I do not see a text "8,40 €"
        Then I do not see a text "Sin asignar"

    Scenario: An owner of another account sees nothing of this one
        When I am logged as "other@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I see a text "Todavía no hay importes devengados a su favor"
        Then I do not see a text "REF-1"

    Scenario: An owner sees their payout once it has been created and can download its statement
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I see a text "Mis liquidaciones"
        Then I see a text "Liquidado"

    Scenario: An owner cannot download the statement of a payout of another account
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I am logged as "other@dediez.software"
        When I go to the only payout statement as a regular user
        Then the response status should be 403
