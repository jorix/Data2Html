"use strict";

var helpers = require("../../helpers/helpers");

exports["US/Pacific"] = {
	"1918" : helpers.makeTestYear("US/Pacific", [
		["1918-03-31T09:59:59+00:00", "01:59:59", "PST", 480],
		["1918-03-31T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1918-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1918-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1919" : helpers.makeTestYear("US/Pacific", [
		["1919-03-30T09:59:59+00:00", "01:59:59", "PST", 480],
		["1919-03-30T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1919-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1919-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1942" : helpers.makeTestYear("US/Pacific", [
		["1942-02-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["1942-02-09T10:00:00+00:00", "03:00:00", "PWT", 420]
	]),

	"1945" : helpers.makeTestYear("US/Pacific", [
		["1945-08-14T22:59:59+00:00", "15:59:59", "PWT", 420],
		["1945-08-14T23:00:00+00:00", "16:00:00", "PPT", 420],
		["1945-09-30T08:59:59+00:00", "01:59:59", "PPT", 420],
		["1945-09-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1948" : helpers.makeTestYear("US/Pacific", [
		["1948-03-14T10:00:59+00:00", "02:00:59", "PST", 480],
		["1948-03-14T10:01:00+00:00", "03:01:00", "PDT", 420]
	]),

	"1949" : helpers.makeTestYear("US/Pacific", [
		["1949-01-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1949-01-01T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1950" : helpers.makeTestYear("US/Pacific", [
		["1950-04-30T08:59:59+00:00", "00:59:59", "PST", 480],
		["1950-04-30T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1950-09-24T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1950-09-24T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1951" : helpers.makeTestYear("US/Pacific", [
		["1951-04-29T08:59:59+00:00", "00:59:59", "PST", 480],
		["1951-04-29T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1951-09-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1951-09-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1952" : helpers.makeTestYear("US/Pacific", [
		["1952-04-27T08:59:59+00:00", "00:59:59", "PST", 480],
		["1952-04-27T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1952-09-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1952-09-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1953" : helpers.makeTestYear("US/Pacific", [
		["1953-04-26T08:59:59+00:00", "00:59:59", "PST", 480],
		["1953-04-26T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1953-09-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1953-09-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1954" : helpers.makeTestYear("US/Pacific", [
		["1954-04-25T08:59:59+00:00", "00:59:59", "PST", 480],
		["1954-04-25T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1954-09-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1954-09-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1955" : helpers.makeTestYear("US/Pacific", [
		["1955-04-24T08:59:59+00:00", "00:59:59", "PST", 480],
		["1955-04-24T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1955-09-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1955-09-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1956" : helpers.makeTestYear("US/Pacific", [
		["1956-04-29T08:59:59+00:00", "00:59:59", "PST", 480],
		["1956-04-29T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1956-09-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1956-09-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1957" : helpers.makeTestYear("US/Pacific", [
		["1957-04-28T08:59:59+00:00", "00:59:59", "PST", 480],
		["1957-04-28T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1957-09-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1957-09-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1958" : helpers.makeTestYear("US/Pacific", [
		["1958-04-27T08:59:59+00:00", "00:59:59", "PST", 480],
		["1958-04-27T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1958-09-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1958-09-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1959" : helpers.makeTestYear("US/Pacific", [
		["1959-04-26T08:59:59+00:00", "00:59:59", "PST", 480],
		["1959-04-26T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1959-09-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1959-09-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1960" : helpers.makeTestYear("US/Pacific", [
		["1960-04-24T08:59:59+00:00", "00:59:59", "PST", 480],
		["1960-04-24T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1960-09-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1960-09-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1961" : helpers.makeTestYear("US/Pacific", [
		["1961-04-30T08:59:59+00:00", "00:59:59", "PST", 480],
		["1961-04-30T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1961-09-24T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1961-09-24T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1962" : helpers.makeTestYear("US/Pacific", [
		["1962-04-29T08:59:59+00:00", "00:59:59", "PST", 480],
		["1962-04-29T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1962-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1962-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1963" : helpers.makeTestYear("US/Pacific", [
		["1963-04-28T08:59:59+00:00", "00:59:59", "PST", 480],
		["1963-04-28T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1963-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1963-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1964" : helpers.makeTestYear("US/Pacific", [
		["1964-04-26T08:59:59+00:00", "00:59:59", "PST", 480],
		["1964-04-26T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1964-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1964-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1965" : helpers.makeTestYear("US/Pacific", [
		["1965-04-25T08:59:59+00:00", "00:59:59", "PST", 480],
		["1965-04-25T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1965-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1965-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1966" : helpers.makeTestYear("US/Pacific", [
		["1966-04-24T08:59:59+00:00", "00:59:59", "PST", 480],
		["1966-04-24T09:00:00+00:00", "02:00:00", "PDT", 420],
		["1966-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1966-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1967" : helpers.makeTestYear("US/Pacific", [
		["1967-04-30T09:59:59+00:00", "01:59:59", "PST", 480],
		["1967-04-30T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1967-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1967-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1968" : helpers.makeTestYear("US/Pacific", [
		["1968-04-28T09:59:59+00:00", "01:59:59", "PST", 480],
		["1968-04-28T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1968-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1968-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1969" : helpers.makeTestYear("US/Pacific", [
		["1969-04-27T09:59:59+00:00", "01:59:59", "PST", 480],
		["1969-04-27T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1969-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1969-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1970" : helpers.makeTestYear("US/Pacific", [
		["1970-04-26T09:59:59+00:00", "01:59:59", "PST", 480],
		["1970-04-26T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1970-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1970-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1971" : helpers.makeTestYear("US/Pacific", [
		["1971-04-25T09:59:59+00:00", "01:59:59", "PST", 480],
		["1971-04-25T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1971-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1971-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1972" : helpers.makeTestYear("US/Pacific", [
		["1972-04-30T09:59:59+00:00", "01:59:59", "PST", 480],
		["1972-04-30T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1972-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1972-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1973" : helpers.makeTestYear("US/Pacific", [
		["1973-04-29T09:59:59+00:00", "01:59:59", "PST", 480],
		["1973-04-29T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1973-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1973-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1974" : helpers.makeTestYear("US/Pacific", [
		["1974-01-06T09:59:59+00:00", "01:59:59", "PST", 480],
		["1974-01-06T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1974-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1974-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1975" : helpers.makeTestYear("US/Pacific", [
		["1975-02-23T09:59:59+00:00", "01:59:59", "PST", 480],
		["1975-02-23T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1975-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1975-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1976" : helpers.makeTestYear("US/Pacific", [
		["1976-04-25T09:59:59+00:00", "01:59:59", "PST", 480],
		["1976-04-25T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1976-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1976-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1977" : helpers.makeTestYear("US/Pacific", [
		["1977-04-24T09:59:59+00:00", "01:59:59", "PST", 480],
		["1977-04-24T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1977-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1977-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1978" : helpers.makeTestYear("US/Pacific", [
		["1978-04-30T09:59:59+00:00", "01:59:59", "PST", 480],
		["1978-04-30T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1978-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1978-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1979" : helpers.makeTestYear("US/Pacific", [
		["1979-04-29T09:59:59+00:00", "01:59:59", "PST", 480],
		["1979-04-29T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1979-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1979-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1980" : helpers.makeTestYear("US/Pacific", [
		["1980-04-27T09:59:59+00:00", "01:59:59", "PST", 480],
		["1980-04-27T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1980-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1980-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1981" : helpers.makeTestYear("US/Pacific", [
		["1981-04-26T09:59:59+00:00", "01:59:59", "PST", 480],
		["1981-04-26T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1981-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1981-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1982" : helpers.makeTestYear("US/Pacific", [
		["1982-04-25T09:59:59+00:00", "01:59:59", "PST", 480],
		["1982-04-25T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1982-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1982-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1983" : helpers.makeTestYear("US/Pacific", [
		["1983-04-24T09:59:59+00:00", "01:59:59", "PST", 480],
		["1983-04-24T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1983-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1983-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1984" : helpers.makeTestYear("US/Pacific", [
		["1984-04-29T09:59:59+00:00", "01:59:59", "PST", 480],
		["1984-04-29T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1984-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1984-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1985" : helpers.makeTestYear("US/Pacific", [
		["1985-04-28T09:59:59+00:00", "01:59:59", "PST", 480],
		["1985-04-28T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1985-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1985-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1986" : helpers.makeTestYear("US/Pacific", [
		["1986-04-27T09:59:59+00:00", "01:59:59", "PST", 480],
		["1986-04-27T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1986-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1986-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1987" : helpers.makeTestYear("US/Pacific", [
		["1987-04-05T09:59:59+00:00", "01:59:59", "PST", 480],
		["1987-04-05T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1987-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1987-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1988" : helpers.makeTestYear("US/Pacific", [
		["1988-04-03T09:59:59+00:00", "01:59:59", "PST", 480],
		["1988-04-03T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1988-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1988-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1989" : helpers.makeTestYear("US/Pacific", [
		["1989-04-02T09:59:59+00:00", "01:59:59", "PST", 480],
		["1989-04-02T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1989-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1989-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1990" : helpers.makeTestYear("US/Pacific", [
		["1990-04-01T09:59:59+00:00", "01:59:59", "PST", 480],
		["1990-04-01T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1990-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1990-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1991" : helpers.makeTestYear("US/Pacific", [
		["1991-04-07T09:59:59+00:00", "01:59:59", "PST", 480],
		["1991-04-07T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1991-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1991-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1992" : helpers.makeTestYear("US/Pacific", [
		["1992-04-05T09:59:59+00:00", "01:59:59", "PST", 480],
		["1992-04-05T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1992-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1992-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1993" : helpers.makeTestYear("US/Pacific", [
		["1993-04-04T09:59:59+00:00", "01:59:59", "PST", 480],
		["1993-04-04T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1993-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1993-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1994" : helpers.makeTestYear("US/Pacific", [
		["1994-04-03T09:59:59+00:00", "01:59:59", "PST", 480],
		["1994-04-03T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1994-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1994-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1995" : helpers.makeTestYear("US/Pacific", [
		["1995-04-02T09:59:59+00:00", "01:59:59", "PST", 480],
		["1995-04-02T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1995-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1995-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1996" : helpers.makeTestYear("US/Pacific", [
		["1996-04-07T09:59:59+00:00", "01:59:59", "PST", 480],
		["1996-04-07T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1996-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1996-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1997" : helpers.makeTestYear("US/Pacific", [
		["1997-04-06T09:59:59+00:00", "01:59:59", "PST", 480],
		["1997-04-06T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1997-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1997-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1998" : helpers.makeTestYear("US/Pacific", [
		["1998-04-05T09:59:59+00:00", "01:59:59", "PST", 480],
		["1998-04-05T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1998-10-25T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1998-10-25T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"1999" : helpers.makeTestYear("US/Pacific", [
		["1999-04-04T09:59:59+00:00", "01:59:59", "PST", 480],
		["1999-04-04T10:00:00+00:00", "03:00:00", "PDT", 420],
		["1999-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["1999-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2000" : helpers.makeTestYear("US/Pacific", [
		["2000-04-02T09:59:59+00:00", "01:59:59", "PST", 480],
		["2000-04-02T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2000-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2000-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2001" : helpers.makeTestYear("US/Pacific", [
		["2001-04-01T09:59:59+00:00", "01:59:59", "PST", 480],
		["2001-04-01T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2001-10-28T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2001-10-28T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2002" : helpers.makeTestYear("US/Pacific", [
		["2002-04-07T09:59:59+00:00", "01:59:59", "PST", 480],
		["2002-04-07T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2002-10-27T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2002-10-27T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2003" : helpers.makeTestYear("US/Pacific", [
		["2003-04-06T09:59:59+00:00", "01:59:59", "PST", 480],
		["2003-04-06T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2003-10-26T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2003-10-26T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2004" : helpers.makeTestYear("US/Pacific", [
		["2004-04-04T09:59:59+00:00", "01:59:59", "PST", 480],
		["2004-04-04T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2004-10-31T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2004-10-31T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2005" : helpers.makeTestYear("US/Pacific", [
		["2005-04-03T09:59:59+00:00", "01:59:59", "PST", 480],
		["2005-04-03T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2005-10-30T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2005-10-30T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2006" : helpers.makeTestYear("US/Pacific", [
		["2006-04-02T09:59:59+00:00", "01:59:59", "PST", 480],
		["2006-04-02T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2006-10-29T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2006-10-29T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2007" : helpers.makeTestYear("US/Pacific", [
		["2007-03-11T09:59:59+00:00", "01:59:59", "PST", 480],
		["2007-03-11T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2007-11-04T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2007-11-04T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2008" : helpers.makeTestYear("US/Pacific", [
		["2008-03-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["2008-03-09T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2008-11-02T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2008-11-02T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2009" : helpers.makeTestYear("US/Pacific", [
		["2009-03-08T09:59:59+00:00", "01:59:59", "PST", 480],
		["2009-03-08T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2009-11-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2009-11-01T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2010" : helpers.makeTestYear("US/Pacific", [
		["2010-03-14T09:59:59+00:00", "01:59:59", "PST", 480],
		["2010-03-14T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2010-11-07T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2010-11-07T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2011" : helpers.makeTestYear("US/Pacific", [
		["2011-03-13T09:59:59+00:00", "01:59:59", "PST", 480],
		["2011-03-13T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2011-11-06T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2011-11-06T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2012" : helpers.makeTestYear("US/Pacific", [
		["2012-03-11T09:59:59+00:00", "01:59:59", "PST", 480],
		["2012-03-11T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2012-11-04T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2012-11-04T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2013" : helpers.makeTestYear("US/Pacific", [
		["2013-03-10T09:59:59+00:00", "01:59:59", "PST", 480],
		["2013-03-10T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2013-11-03T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2013-11-03T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2014" : helpers.makeTestYear("US/Pacific", [
		["2014-03-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["2014-03-09T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2014-11-02T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2014-11-02T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2015" : helpers.makeTestYear("US/Pacific", [
		["2015-03-08T09:59:59+00:00", "01:59:59", "PST", 480],
		["2015-03-08T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2015-11-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2015-11-01T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2016" : helpers.makeTestYear("US/Pacific", [
		["2016-03-13T09:59:59+00:00", "01:59:59", "PST", 480],
		["2016-03-13T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2016-11-06T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2016-11-06T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2017" : helpers.makeTestYear("US/Pacific", [
		["2017-03-12T09:59:59+00:00", "01:59:59", "PST", 480],
		["2017-03-12T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2017-11-05T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2017-11-05T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2018" : helpers.makeTestYear("US/Pacific", [
		["2018-03-11T09:59:59+00:00", "01:59:59", "PST", 480],
		["2018-03-11T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2018-11-04T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2018-11-04T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2019" : helpers.makeTestYear("US/Pacific", [
		["2019-03-10T09:59:59+00:00", "01:59:59", "PST", 480],
		["2019-03-10T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2019-11-03T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2019-11-03T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2020" : helpers.makeTestYear("US/Pacific", [
		["2020-03-08T09:59:59+00:00", "01:59:59", "PST", 480],
		["2020-03-08T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2020-11-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2020-11-01T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2021" : helpers.makeTestYear("US/Pacific", [
		["2021-03-14T09:59:59+00:00", "01:59:59", "PST", 480],
		["2021-03-14T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2021-11-07T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2021-11-07T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2022" : helpers.makeTestYear("US/Pacific", [
		["2022-03-13T09:59:59+00:00", "01:59:59", "PST", 480],
		["2022-03-13T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2022-11-06T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2022-11-06T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2023" : helpers.makeTestYear("US/Pacific", [
		["2023-03-12T09:59:59+00:00", "01:59:59", "PST", 480],
		["2023-03-12T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2023-11-05T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2023-11-05T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2024" : helpers.makeTestYear("US/Pacific", [
		["2024-03-10T09:59:59+00:00", "01:59:59", "PST", 480],
		["2024-03-10T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2024-11-03T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2024-11-03T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2025" : helpers.makeTestYear("US/Pacific", [
		["2025-03-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["2025-03-09T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2025-11-02T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2025-11-02T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2026" : helpers.makeTestYear("US/Pacific", [
		["2026-03-08T09:59:59+00:00", "01:59:59", "PST", 480],
		["2026-03-08T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2026-11-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2026-11-01T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2027" : helpers.makeTestYear("US/Pacific", [
		["2027-03-14T09:59:59+00:00", "01:59:59", "PST", 480],
		["2027-03-14T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2027-11-07T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2027-11-07T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2028" : helpers.makeTestYear("US/Pacific", [
		["2028-03-12T09:59:59+00:00", "01:59:59", "PST", 480],
		["2028-03-12T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2028-11-05T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2028-11-05T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2029" : helpers.makeTestYear("US/Pacific", [
		["2029-03-11T09:59:59+00:00", "01:59:59", "PST", 480],
		["2029-03-11T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2029-11-04T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2029-11-04T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2030" : helpers.makeTestYear("US/Pacific", [
		["2030-03-10T09:59:59+00:00", "01:59:59", "PST", 480],
		["2030-03-10T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2030-11-03T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2030-11-03T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2031" : helpers.makeTestYear("US/Pacific", [
		["2031-03-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["2031-03-09T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2031-11-02T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2031-11-02T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2032" : helpers.makeTestYear("US/Pacific", [
		["2032-03-14T09:59:59+00:00", "01:59:59", "PST", 480],
		["2032-03-14T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2032-11-07T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2032-11-07T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2033" : helpers.makeTestYear("US/Pacific", [
		["2033-03-13T09:59:59+00:00", "01:59:59", "PST", 480],
		["2033-03-13T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2033-11-06T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2033-11-06T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2034" : helpers.makeTestYear("US/Pacific", [
		["2034-03-12T09:59:59+00:00", "01:59:59", "PST", 480],
		["2034-03-12T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2034-11-05T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2034-11-05T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2035" : helpers.makeTestYear("US/Pacific", [
		["2035-03-11T09:59:59+00:00", "01:59:59", "PST", 480],
		["2035-03-11T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2035-11-04T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2035-11-04T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2036" : helpers.makeTestYear("US/Pacific", [
		["2036-03-09T09:59:59+00:00", "01:59:59", "PST", 480],
		["2036-03-09T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2036-11-02T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2036-11-02T09:00:00+00:00", "01:00:00", "PST", 480]
	]),

	"2037" : helpers.makeTestYear("US/Pacific", [
		["2037-03-08T09:59:59+00:00", "01:59:59", "PST", 480],
		["2037-03-08T10:00:00+00:00", "03:00:00", "PDT", 420],
		["2037-11-01T08:59:59+00:00", "01:59:59", "PDT", 420],
		["2037-11-01T09:00:00+00:00", "01:00:00", "PST", 480]
	])
};